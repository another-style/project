<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    private const MAX_DEPTH = 10;

    /**
     * Главная страница — список корневых комментариев (тем).
     */
    public function index(): Response
    {
        $topics = Comment::roots()
            ->orderByDesc('last_comment_at')
            ->paginate(20);

        $lastCommentIds = $topics->getCollection()
            ->filter(fn ($t) => $t->last_comment_id && $t->last_comment_id !== $t->id)
            ->pluck('last_comment_id')
            ->unique()
            ->values();

        $lastComments = collect();
        if ($lastCommentIds->isNotEmpty()) {
            $lastComments = Comment::withDepth()
                ->whereIn('id', $lastCommentIds)
                ->get()
                ->keyBy('id');
        }

        $topics->getCollection()->transform(function ($topic) use ($lastComments) {
            $topic->last_comment_link = null;
            if ($topic->last_comment_id && $lastComments->has($topic->last_comment_id)) {
                $lastComment = $lastComments->get($topic->last_comment_id);
                if ($lastComment->depth > self::MAX_DEPTH) {
                    $topic->last_comment_link = route('comments.show', $lastComment->parent_id);
                }
            }

            return $topic;
        });

        return Inertia::render('Home', [
            'topics' => $topics,
        ]);
    }

    /**
     * Страница конкретного комментария с деревом ответов.
     */
    public function show(Comment $comment): Response
    {
        $descendants = $comment->descendants()
            ->defaultOrder()
            ->get();

        $tree = $this->buildTreeFromCollection($comment, $descendants, self::MAX_DEPTH);

        return Inertia::render('Comments/Show', [
            'comment' => $comment->only(['id', 'name', 'message', 'created_at', 'parent_id']),
            'children' => $tree,
        ]);
    }

    /**
     * Создание нового комментария (тема или ответ).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = Comment::create([
            'name' => $validated['name'] ?? null,
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        if ($comment->parent_id) {
            return redirect()->route('comments.show', $comment->parent_id)
                ->with('success', 'Комментарий добавлен.');
        }

        return redirect()->route('comments.show', $comment->id)
            ->with('success', 'Тема создана.');
    }

    /**
     * Подгрузка потомков комментария (JSON).
     */
    public function loadMore(Comment $comment): JsonResponse
    {
        $descendants = $comment->descendants()
            ->defaultOrder()
            ->get();

        $tree = $this->buildTreeFromCollection($comment, $descendants);

        return response()->json([
            'children' => $tree,
        ]);
    }

    /**
     * Построить дерево из коллекции потомков с ограничением глубины.
     */
    private function buildTreeFromCollection(Comment $root, $descendants, ?int $maxDepth = null): array
    {
        $grouped = $descendants->groupBy('parent_id');

        $buildLevel = function ($parentId, int $currentDepth) use (&$buildLevel, $grouped, $maxDepth) {
            $children = $grouped->get($parentId, collect());

            if ($children->isEmpty()) {
                return [];
            }

            if ($maxDepth !== null && $currentDepth >= $maxDepth) {
                return $children->map(function ($child) use ($grouped) {
                    $hasReplies = $grouped->has($child->id);

                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'message' => $child->message,
                        'created_at' => $child->created_at,
                        'parent_id' => $child->parent_id,
                        'children' => [],
                        'has_more_replies' => $hasReplies,
                    ];
                })->values()->all();
            }

            return $children->map(function ($child) use ($buildLevel, $currentDepth) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'message' => $child->message,
                    'created_at' => $child->created_at,
                    'parent_id' => $child->parent_id,
                    'children' => $buildLevel($child->id, $currentDepth + 1),
                    'has_more_replies' => false,
                ];
            })->values()->all();
        };

        return $buildLevel($root->id, 1);
    }
}

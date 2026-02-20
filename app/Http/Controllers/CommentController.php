<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    private const MAX_DEPTH = 10;

    /**
     * Главная страница — список корневых комментариев (тем).
     */
    public function index(Request $request): Response
    {
        $query = Comment::roots()
            ->with('tags');

        // Фильтрация по тегу
        $currentTag = $request->query('tag');
        if ($currentTag) {
            $query->whereHas('tags', function ($q) use ($currentTag) {
                $q->where('name', $currentTag);
            });
        }

        $topics = $query->orderByDesc('last_comment_at')
            ->paginate(20)
            ->withQueryString();

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

        $topicIds = $topics->getCollection()->pluck('id');
        $userVotes = $this->getUserVotes($topicIds, $request->ip());

        $topics->getCollection()->transform(function ($topic) use ($lastComments, $userVotes) {
            $topic->user_vote = $userVotes->get($topic->id);
            $topic->last_comment_link = null;
            if ($topic->last_comment_id && $lastComments->has($topic->last_comment_id)) {
                $lastComment = $lastComments->get($topic->last_comment_id);
                if ($lastComment->depth > self::MAX_DEPTH) {
                    $topic->last_comment_link = route('comments.show', $lastComment->parent_id);
                }
            }

            return $topic;
        });

        // Все теги с количеством тем
        $allTags = Tag::whereHas('comments', function ($q) {
            $q->whereNull('parent_id');
        })
            ->withCount(['comments' => function ($q) {
                $q->whereNull('parent_id');
            }])
            ->orderBy('name')
            ->get()
            ->map(fn ($tag) => [
                'name' => $tag->name,
                'count' => $tag->comments_count,
            ]);

        return Inertia::render('Home', [
            'topics' => $topics,
            'allTags' => $allTags,
            'currentTag' => $currentTag,
        ]);
    }

    /**
     * Страница конкретного комментария с деревом ответов.
     */
    public function show(Request $request, Comment $comment): Response
    {
        $descendants = $comment->descendants()
            ->defaultOrder()
            ->get();

        $ip = $request->ip();
        $allCommentIds = $descendants->pluck('id')->push($comment->id);
        $userVotes = $this->getUserVotes($allCommentIds, $ip);

        $tree = $this->buildTreeFromCollection($comment, $descendants, self::MAX_DEPTH, $userVotes);

        return Inertia::render('Comments/Show', [
            'comment' => array_merge(
                $comment->only(['id', 'name', 'message', 'created_at', 'parent_id']),
                [
                    'message_html' => $comment->message_html,
                    'likes_count' => $comment->likes_count,
                    'dislikes_count' => $comment->dislikes_count,
                    'user_vote' => $userVotes->get($comment->id),
                ]
            ),
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
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $comment = Comment::create([
            'name' => $validated['name'] ?? null,
            'message' => $validated['message'],
            'ip_address' => $request->ip(),
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Теги добавляются только для корневых комментариев (тем)
        if ($comment->parent_id === null && ! empty($validated['tags'])) {
            $tagIds = collect($validated['tags'])
                ->map(fn ($name) => Tag::firstOrCreate(['name' => trim($name)])->id);
            $comment->tags()->sync($tagIds);
        }

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
    public function loadMore(Request $request, Comment $comment): JsonResponse
    {
        $descendants = $comment->descendants()
            ->defaultOrder()
            ->get();

        $ip = $request->ip();
        $allCommentIds = $descendants->pluck('id')->push($comment->id);
        $userVotes = $this->getUserVotes($allCommentIds, $ip);

        $tree = $this->buildTreeFromCollection($comment, $descendants, null, $userVotes);

        return response()->json([
            'children' => $tree,
        ]);
    }

    /**
     * Голосование за комментарий (лайк/дизлайк).
     */
    public function vote(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'vote' => ['required', 'in:1,-1'],
        ]);

        $ip = $request->ip();
        $voteValue = (int) $validated['vote'];

        $existingVote = CommentVote::where('comment_id', $comment->id)
            ->where('ip_address', $ip)
            ->first();

        if ($existingVote) {
            if ($existingVote->vote === $voteValue) {
                $existingVote->delete();
            } else {
                $existingVote->update(['vote' => $voteValue]);
            }
        } else {
            CommentVote::create([
                'comment_id' => $comment->id,
                'ip_address' => $ip,
                'vote' => $voteValue,
            ]);
        }

        $comment->update([
            'likes_count' => $comment->votes()->where('vote', 1)->count(),
            'dislikes_count' => $comment->votes()->where('vote', -1)->count(),
        ]);

        $currentVote = CommentVote::where('comment_id', $comment->id)
            ->where('ip_address', $ip)
            ->first();

        return response()->json([
            'likes_count' => $comment->likes_count,
            'dislikes_count' => $comment->dislikes_count,
            'user_vote' => $currentVote?->vote,
        ]);
    }

    /**
     * Получить голоса пользователя по IP для набора комментариев.
     */
    private function getUserVotes(Collection $commentIds, string $ip): Collection
    {
        return CommentVote::whereIn('comment_id', $commentIds)
            ->where('ip_address', $ip)
            ->pluck('vote', 'comment_id');
    }

    /**
     * Построить дерево из коллекции потомков с ограничением глубины.
     */
    private function buildTreeFromCollection(Comment $root, $descendants, ?int $maxDepth = null, ?Collection $userVotes = null): array
    {
        $grouped = $descendants->groupBy('parent_id');

        $buildLevel = function ($parentId, int $currentDepth) use (&$buildLevel, $grouped, $maxDepth, $userVotes) {
            $children = $grouped->get($parentId, collect());

            if ($children->isEmpty()) {
                return [];
            }

            if ($maxDepth !== null && $currentDepth >= $maxDepth) {
                return $children->map(function ($child) use ($grouped, $userVotes) {
                    $hasReplies = $grouped->has($child->id);

                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'message' => $child->message,
                        'message_html' => $child->message_html,
                        'created_at' => $child->created_at,
                        'parent_id' => $child->parent_id,
                        'likes_count' => $child->likes_count,
                        'dislikes_count' => $child->dislikes_count,
                        'user_vote' => $userVotes?->get($child->id),
                        'children' => [],
                        'has_more_replies' => $hasReplies,
                    ];
                })->values()->all();
            }

            return $children->map(function ($child) use ($buildLevel, $currentDepth, $userVotes) {
                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'message' => $child->message,
                    'message_html' => $child->message_html,
                    'created_at' => $child->created_at,
                    'parent_id' => $child->parent_id,
                    'likes_count' => $child->likes_count,
                    'dislikes_count' => $child->dislikes_count,
                    'user_vote' => $userVotes?->get($child->id),
                    'children' => $buildLevel($child->id, $currentDepth + 1),
                    'has_more_replies' => false,
                ];
            })->values()->all();
        };

        return $buildLevel($root->id, 1);
    }
}

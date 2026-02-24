<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Services\MarkdownService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentReferenceMarkdownTest extends TestCase
{
    use RefreshDatabase;

    private MarkdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Используем сервис с дефолтным резолвером (реальная БД)
        $this->service = new MarkdownService();
    }

    public function test_existing_comment_renders_as_blockquote_link_with_message(): void
    {
        $comment = Comment::create([
            'message' => 'Hello **from** comment',
            'ip_address' => '127.0.0.1',
        ]);

        $result = $this->service->toHtml(">>{$comment->id}");

        $this->assertStringContainsString('<blockquote class="reply-quote">', $result);
        $this->assertStringContainsString('<a href="/comments/' . $comment->id . '">&gt;&gt;' . $comment->id . '</a>', $result);
        // Markdown в цитируемом комментарии отрендерен
        $this->assertStringContainsString('<strong>from</strong>', $result);
        // Обёртка <p> вокруг blockquote убрана
        $this->assertStringNotContainsString('<p><blockquote', $result);
    }

    public function test_nonexistent_comment_renders_as_plain_text(): void
    {
        // ID, которого заведомо нет в пустой БД
        $result = $this->service->toHtml('>>99999');

        $this->assertStringNotContainsString('<blockquote class="reply-quote">', $result);
        $this->assertStringNotContainsString('<a', $result);
        $this->assertStringContainsString('&gt;&gt;99999', $result);
    }

    public function test_soft_deleted_comment_renders_as_plain_text(): void
    {
        $comment = Comment::create([
            'message' => 'deleted comment',
            'ip_address' => '127.0.0.1',
        ]);
        $id = $comment->id;
        $comment->delete();

        $result = $this->service->toHtml(">>{$id}");

        $this->assertStringNotContainsString('<blockquote class="reply-quote">', $result);
        $this->assertStringNotContainsString('<a', $result);
        $this->assertStringContainsString('&gt;&gt;' . $id, $result);
    }
}

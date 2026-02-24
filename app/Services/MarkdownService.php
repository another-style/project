<?php

namespace App\Services;

use App\Models\Comment;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownService
{
    private MarkdownConverter $converter;

    /**
     * Принимает ID комментария, возвращает безопасный HTML сообщения
     * или null если комментарий не найден / удалён.
     *
     * @var callable(int): ?string
     */
    private $commentResolver;

    /**
     * @param callable(int): ?string|null $commentResolver
     */
    public function __construct(?callable $commentResolver = null)
    {
        $this->commentResolver = $commentResolver
            ?? static function (int $id): ?string {
                $comment = Comment::find($id);
                if ($comment === null) {
                    return null;
                }
                // Рендерим markdown цитируемого комментария без рекурсивного
                // разворачивания >>ID, чтобы не допустить бесконечной рекурсии.
                return (new MarkdownService(static fn () => null))->toHtml($comment->message);
            };

        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'renderer' => [
                'soft_break' => '<br>',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new StrikethroughExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    public function toHtml(string $markdown): string
    {
        // Экранируем >>ID в начале строк, чтобы CommonMark не распарсил их
        // как вложенные blockquote (блочный уровень).
        $markdown = $this->preprocessCommentReferences($markdown);

        $markdown = $this->disableLazyContinuation($markdown);

        $html = $this->converter->convert($markdown)->getContent();

        // Оставляем только безопасные теги
        $html = strip_tags($html, [
            'p', 'br', 'strong', 'em', 'code', 'pre', 'del',
            'ul', 'ol', 'li', 'blockquote',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr',
        ]);

        // Заменяем >>ID (в виде &gt;&gt;ID) на ссылки.
        // Выполняется после strip_tags, чтобы тег <a> не был удалён.
        $html = $this->resolveCommentReferences($html);

        // Убираем <p>, если внутри только цитаты-ссылки и <br>.
        $html = $this->unwrapReplyQuoteParagraphs($html);

        return trim($html);
    }

    /**
     * Заменяет >>ID в начале строк на &gt;>ID, чтобы CommonMark не интерпретировал
     * ">>" как маркер вложенной blockquote. После рендеринга CommonMark
     * &gt;>ID превратится в &gt;&gt;ID в HTML, которое обрабатывается в resolveCommentReferences.
     *
     * Строки внутри огороженных блоков кода (```) не затрагиваются.
     */
    private function preprocessCommentReferences(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $result = [];
        $inCodeFence = false;

        foreach ($lines as $line) {
            if (preg_match('/^[ \t]{0,3}(`{3,}|~{3,})/', $line)) {
                $inCodeFence = !$inCodeFence;
                $result[] = $line;
                continue;
            }

            if (!$inCodeFence) {
                $line = (string) preg_replace('/^([ \t]{0,3})>>(\d+)/', '$1&gt;>$2', $line);
            }

            $result[] = $line;
        }

        return implode("\n", $result);
    }

    /**
     * Заменяет вхождения вида &gt;&gt;ID на ссылку на комментарий.
     * Вхождения внутри тегов <code> и <pre> пропускаются.
     *
     * Если комментарий найден:
     *   <blockquote class="reply-quote"><a href="/comments/ID">&gt;&gt;ID текст</a></blockquote>
     *
     * Если не найден: оставляет &gt;&gt;ID как обычный текст.
     */
    private function resolveCommentReferences(string $html): string
    {
        // (*SKIP)(*FAIL) пропускает совпадения внутри <pre> и <code>.
        return (string) preg_replace_callback(
            '/<pre>.*?<\/pre>(*SKIP)(*FAIL)|<code>.*?<\/code>(*SKIP)(*FAIL)|&gt;&gt;(\d+)/s',
            function (array $matches): string {
                // Совпадение <pre> или <code> — возвращаем без изменений
                if (!isset($matches[1])) {
                    return $matches[0];
                }

                $id = (int) $matches[1];
                $messageHtml = ($this->commentResolver)($id);

                if ($messageHtml === null) {
                    return '&gt;&gt;' . $id;
                }

                // <a> содержит только ссылку-идентификатор; HTML сообщения идёт следом
                // внутри <blockquote>, чтобы не нарушать структуру (блочные элементы
                // не могут быть внутри <a>).
                return '<blockquote class="reply-quote"><a href="/comments/' . $id . '">&gt;&gt;' . $id . '</a>' . $messageHtml . '</blockquote>';
            },
            $html,
        );
    }

    /**
     * Убирает тег <p>, если он содержит только цитаты-ссылки (reply-quote blockquote)
     * и теги <br> между ними.
     */
    private function unwrapReplyQuoteParagraphs(string $html): string
    {
        return (string) preg_replace(
            '/<p>((?:\s*(?:<blockquote class="reply-quote">.*?<\/blockquote>|<br\s*\/?>)\s*)*)<\/p>/s',
            '$1',
            $html,
        );
    }

    /**
     * CommonMark по спецификации допускает «lazy continuation»: строка без `>`
     * сразу после цитаты считается её продолжением, а не новым абзацем.
     * Чтобы такое поведение не удивляло пользователей, вставляем пустую строку
     * между последней строкой цитаты и следующей непустой строкой без `>`.
     */
    private function disableLazyContinuation(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $result = [];

        foreach ($lines as $i => $line) {
            $result[] = $line;

            $isQuote = str_starts_with(ltrim($line), '>');

            if ($isQuote && isset($lines[$i + 1])) {
                $next = $lines[$i + 1];
                $nextIsQuote = str_starts_with(ltrim($next), '>');
                $nextIsBlank = trim($next) === '';

                if (!$nextIsQuote && !$nextIsBlank) {
                    $result[] = '';
                }
            }
        }

        return implode("\n", $result);
    }
}

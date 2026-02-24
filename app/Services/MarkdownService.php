<?php

namespace App\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownService
{
    private MarkdownConverter $converter;

    public function __construct()
    {
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
        $markdown = $this->disableLazyContinuation($markdown);

        $html = $this->converter->convert($markdown)->getContent();

        // Оставляем только безопасные теги
        $html = strip_tags($html, [
            'p', 'br', 'strong', 'em', 'code', 'pre', 'del',
            'ul', 'ol', 'li', 'blockquote',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr',
        ]);

        return trim($html);
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

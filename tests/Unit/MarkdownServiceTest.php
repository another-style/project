<?php

namespace Tests\Unit;

use App\Services\MarkdownService;
use PHPUnit\Framework\TestCase;

class MarkdownServiceTest extends TestCase
{
    private MarkdownService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarkdownService();
    }

    // -------------------------------------------------------------------------
    // Разрешённые блочные теги
    // -------------------------------------------------------------------------

    public function test_paragraph_is_allowed(): void
    {
        $result = $this->service->toHtml('Hello world');

        $this->assertStringContainsString('<p>Hello world</p>', $result);
    }

    public function test_h1_is_allowed(): void
    {
        $result = $this->service->toHtml('# Heading 1');

        $this->assertStringContainsString('<h1>Heading 1</h1>', $result);
    }

    public function test_h2_is_allowed(): void
    {
        $result = $this->service->toHtml('## Heading 2');

        $this->assertStringContainsString('<h2>Heading 2</h2>', $result);
    }

    public function test_h3_is_allowed(): void
    {
        $result = $this->service->toHtml('### Heading 3');

        $this->assertStringContainsString('<h3>Heading 3</h3>', $result);
    }

    public function test_h4_is_allowed(): void
    {
        $result = $this->service->toHtml('#### Heading 4');

        $this->assertStringContainsString('<h4>Heading 4</h4>', $result);
    }

    public function test_h5_is_allowed(): void
    {
        $result = $this->service->toHtml('##### Heading 5');

        $this->assertStringContainsString('<h5>Heading 5</h5>', $result);
    }

    public function test_h6_is_allowed(): void
    {
        $result = $this->service->toHtml('###### Heading 6');

        $this->assertStringContainsString('<h6>Heading 6</h6>', $result);
    }

    public function test_hr_is_allowed(): void
    {
        $result = $this->service->toHtml("---");

        $this->assertStringContainsString('<hr', $result);
    }

    public function test_blockquote_is_allowed(): void
    {
        $result = $this->service->toHtml('> quoted text');

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('quoted text', $result);
    }

    public function test_unordered_list_is_allowed(): void
    {
        $result = $this->service->toHtml("- item one\n- item two");

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>item one</li>', $result);
        $this->assertStringContainsString('<li>item two</li>', $result);
    }

    public function test_ordered_list_is_allowed(): void
    {
        $result = $this->service->toHtml("1. first\n2. second");

        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('<li>first</li>', $result);
        $this->assertStringContainsString('<li>second</li>', $result);
    }

    public function test_fenced_code_block_is_allowed(): void
    {
        $result = $this->service->toHtml("```\necho 'hello';\n```");

        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString("echo 'hello';", $result);
    }

    // -------------------------------------------------------------------------
    // Разрешённые строчные теги
    // -------------------------------------------------------------------------

    public function test_strong_is_allowed(): void
    {
        $result = $this->service->toHtml('**bold text**');

        $this->assertStringContainsString('<strong>bold text</strong>', $result);
    }

    public function test_em_is_allowed(): void
    {
        $result = $this->service->toHtml('*italic text*');

        $this->assertStringContainsString('<em>italic text</em>', $result);
    }

    public function test_inline_code_is_allowed(): void
    {
        $result = $this->service->toHtml('`inline code`');

        $this->assertStringContainsString('<code>inline code</code>', $result);
    }

    public function test_del_strikethrough_is_allowed(): void
    {
        $result = $this->service->toHtml('~~strikethrough~~');

        $this->assertStringContainsString('<del>strikethrough</del>', $result);
    }

    public function test_br_from_soft_break_is_allowed(): void
    {
        // Мягкий перенос строки внутри параграфа → <br> (настройка soft_break)
        $result = $this->service->toHtml("line one\nline two");

        $this->assertStringContainsString('<br>', $result);
    }

    // -------------------------------------------------------------------------
    // Запрещённые теги — удаляются, содержимое сохраняется
    // -------------------------------------------------------------------------

    public function test_anchor_tag_is_stripped(): void
    {
        $result = $this->service->toHtml('[click me](https://example.com)');

        $this->assertStringNotContainsString('<a', $result);
        $this->assertStringContainsString('click me', $result);
    }

    public function test_img_tag_is_stripped(): void
    {
        $result = $this->service->toHtml('![alt](https://example.com/img.png)');

        $this->assertStringNotContainsString('<img', $result);
    }

    public function test_inline_html_script_tag_is_stripped(): void
    {
        // html_input => 'strip' удаляет сам тег, но текстовое содержимое между тегами сохраняется.
        // <script> не выполняется — тег убран, в DOM попадает лишь безопасный текст.
        $result = $this->service->toHtml('text <script>alert(1)</script> text');

        $this->assertStringNotContainsString('<script>', $result);
    }

    public function test_inline_html_style_tag_is_stripped(): void
    {
        $result = $this->service->toHtml('text <style>body{color:red}</style> text');

        $this->assertStringNotContainsString('<style>', $result);
    }

    public function test_inline_html_div_tag_is_stripped(): void
    {
        // html_input => 'strip' удаляет блочный HTML-элемент целиком — вместе с содержимым.
        // <div> является HTML-блоком в CommonMark, поэтому весь блок вырезается.
        $result = $this->service->toHtml('<div>content</div>');

        $this->assertStringNotContainsString('<div>', $result);
        $this->assertStringNotContainsString('content', $result);
    }

    public function test_inline_html_span_tag_is_stripped(): void
    {
        $result = $this->service->toHtml('<span>content</span>');

        $this->assertStringNotContainsString('<span>', $result);
        $this->assertStringContainsString('content', $result);
    }

    public function test_inline_html_table_tag_is_stripped(): void
    {
        // CommonMark без GFM tables не рендерит <table>, но если передать сырой HTML
        $result = $this->service->toHtml('<table><tr><td>cell</td></tr></table>');

        $this->assertStringNotContainsString('<table>', $result);
        $this->assertStringNotContainsString('<tr>', $result);
        $this->assertStringNotContainsString('<td>', $result);
    }

    public function test_iframe_tag_is_stripped(): void
    {
        $result = $this->service->toHtml('<iframe src="https://evil.com"></iframe>');

        $this->assertStringNotContainsString('<iframe', $result);
    }

    // -------------------------------------------------------------------------
    // Атрибуты на разрешённых тегах — удаляются strip_tags
    // -------------------------------------------------------------------------

    public function test_attributes_stripped_from_allowed_tags(): void
    {
        // <p onclick="...">text</p> — CommonMark расценивает это как HTML-блок и удаляет
        // его целиком (html_input => 'strip'), поэтому ни тег, ни атрибут, ни текст не проходят.
        $result = $this->service->toHtml('<p onclick="alert(1)">text</p>');

        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('<p onclick', $result);
    }

    // -------------------------------------------------------------------------
    // Комбинации разрешённых тегов
    // -------------------------------------------------------------------------

    public function test_blockquote_with_strong_inside(): void
    {
        $result = $this->service->toHtml('> **important**');

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<strong>important</strong>', $result);
    }

    public function test_blockquote_with_em_inside(): void
    {
        $result = $this->service->toHtml('> *emphasis*');

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<em>emphasis</em>', $result);
    }

    public function test_blockquote_with_code_inside(): void
    {
        $result = $this->service->toHtml('> `code`');

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<code>code</code>', $result);
    }

    public function test_blockquote_with_del_inside(): void
    {
        $result = $this->service->toHtml('> ~~deleted~~');

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<del>deleted</del>', $result);
    }

    public function test_list_item_with_strong(): void
    {
        $result = $this->service->toHtml('- **bold item**');

        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('<strong>bold item</strong>', $result);
    }

    public function test_list_item_with_em(): void
    {
        $result = $this->service->toHtml('- *italic item*');

        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('<em>italic item</em>', $result);
    }

    public function test_list_item_with_code(): void
    {
        $result = $this->service->toHtml('- `code item`');

        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('<code>code item</code>', $result);
    }

    public function test_list_item_with_del(): void
    {
        $result = $this->service->toHtml('- ~~del item~~');

        $this->assertStringContainsString('<li>', $result);
        $this->assertStringContainsString('<del>del item</del>', $result);
    }

    public function test_heading_with_strong(): void
    {
        $result = $this->service->toHtml('# **bold heading**');

        $this->assertStringContainsString('<h1>', $result);
        $this->assertStringContainsString('<strong>bold heading</strong>', $result);
    }

    public function test_heading_with_em(): void
    {
        $result = $this->service->toHtml('# *italic heading*');

        $this->assertStringContainsString('<h1>', $result);
        $this->assertStringContainsString('<em>italic heading</em>', $result);
    }

    public function test_heading_with_code(): void
    {
        $result = $this->service->toHtml('# `code heading`');

        $this->assertStringContainsString('<h1>', $result);
        $this->assertStringContainsString('<code>code heading</code>', $result);
    }

    public function test_heading_with_del(): void
    {
        $result = $this->service->toHtml('# ~~deleted heading~~');

        $this->assertStringContainsString('<h1>', $result);
        $this->assertStringContainsString('<del>deleted heading</del>', $result);
    }

    public function test_paragraph_with_strong_em_combined(): void
    {
        $result = $this->service->toHtml('***bold and italic***');

        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<em>', $result);
        $this->assertStringContainsString('bold and italic', $result);
    }

    public function test_paragraph_with_strong_and_del(): void
    {
        $result = $this->service->toHtml('**bold** and ~~del~~');

        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<del>del</del>', $result);
    }

    public function test_paragraph_with_em_and_code(): void
    {
        $result = $this->service->toHtml('*italic* and `code`');

        $this->assertStringContainsString('<em>italic</em>', $result);
        $this->assertStringContainsString('<code>code</code>', $result);
    }

    public function test_pre_code_with_content(): void
    {
        $result = $this->service->toHtml("```\n\$x = 1;\n```");

        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString('$x = 1;', $result);
    }

    public function test_ordered_list_with_strong_items(): void
    {
        $result = $this->service->toHtml("1. **first**\n2. **second**");

        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('<strong>first</strong>', $result);
        $this->assertStringContainsString('<strong>second</strong>', $result);
    }

    // -------------------------------------------------------------------------
    // Переносы строк и поведение блоков цитат
    // -------------------------------------------------------------------------

    public function test_single_newline_does_not_create_new_paragraph(): void
    {
        // Одиночный \n внутри текста — это soft break → <br>, но НЕ новый параграф
        $result = $this->service->toHtml("first\nsecond");

        $this->assertSame(1, substr_count($result, '<p>'));
        $this->assertStringContainsString('<br>', $result);
    }

    public function test_double_newline_creates_two_paragraphs(): void
    {
        // Двойной \n\n — разделитель параграфов, создаёт два блока <p>
        $result = $this->service->toHtml("first\n\nsecond");

        $this->assertSame(2, substr_count($result, '<p>'));
        $this->assertStringContainsString('<p>first</p>', $result);
        $this->assertStringContainsString('<p>second</p>', $result);
    }

    public function test_hard_break_two_spaces_creates_br(): void
    {
        // Два пробела перед \n → hard break → <br /> (CommonMark рендерит именно <br />)
        $result = $this->service->toHtml("first line  \nsecond line");

        $this->assertStringContainsString('<br />', $result);
        $this->assertStringContainsString('second line', $result);
        // всё ещё один параграф
        $this->assertSame(1, substr_count($result, '<p>'));
    }

    public function test_blockquote_lazy_continuation_is_disabled(): void
    {
        // Сервис подавляет «lazy continuation» CommonMark: строка без `>` сразу
        // после цитаты НЕ становится её продолжением, а рендерится отдельным абзацем.
        $result = $this->service->toHtml("> quote line\nnot a quote");

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('quote line', $result);
        // вторая строка — обычный параграф вне цитаты
        $this->assertStringContainsString('<p>not a quote</p>', $result);
    }

    public function test_blank_line_terminates_blockquote(): void
    {
        // Пустая строка завершает блок цитаты, следующий текст — обычный параграф
        $result = $this->service->toHtml("> quote\n\nnormal paragraph");

        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('quote', $result);
        $this->assertStringContainsString('<p>normal paragraph</p>', $result);
        // только один blockquote
        $this->assertSame(1, substr_count($result, '<blockquote>'));
    }

    public function test_multiline_blockquote_with_marker_on_each_line(): void
    {
        // Все строки с `>` — одна цитата, один <blockquote>
        $result = $this->service->toHtml("> first\n> second\n> third");

        $this->assertSame(1, substr_count($result, '<blockquote>'));
        $this->assertStringContainsString('first', $result);
        $this->assertStringContainsString('second', $result);
        $this->assertStringContainsString('third', $result);
    }

    public function test_text_after_blockquote_and_blank_line_is_paragraph(): void
    {
        // Цитата, пустая строка, обычный текст — текст в отдельном <p> вне цитаты
        $result = $this->service->toHtml("> cite\n\nfirst para\n\nsecond para");

        $this->assertSame(1, substr_count($result, '<blockquote>'));
        $this->assertStringContainsString('<p>first para</p>', $result);
        $this->assertStringContainsString('<p>second para</p>', $result);
    }

    // -------------------------------------------------------------------------
    // Граничные случаи
    // -------------------------------------------------------------------------

    public function test_empty_string_returns_empty(): void
    {
        $result = $this->service->toHtml('');

        $this->assertSame('', $result);
    }

    public function test_result_is_trimmed(): void
    {
        $result = $this->service->toHtml('   ');

        $this->assertSame('', $result);
    }

    public function test_xss_via_javascript_scheme_link_is_stripped(): void
    {
        // allow_unsafe_links => false блокирует javascript: ссылки
        $result = $this->service->toHtml('[xss](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function test_multiple_paragraphs(): void
    {
        $result = $this->service->toHtml("First paragraph.\n\nSecond paragraph.");

        $this->assertStringContainsString('<p>First paragraph.</p>', $result);
        $this->assertStringContainsString('<p>Second paragraph.</p>', $result);
    }

    public function test_nested_blockquote(): void
    {
        $result = $this->service->toHtml("> outer\n>\n>> inner");

        $this->assertSame(2, substr_count($result, '<blockquote>'));
    }

    public function test_nested_list(): void
    {
        $markdown = "- parent\n    - child";
        $result = $this->service->toHtml($markdown);

        $this->assertSame(2, substr_count($result, '<ul>'));
        $this->assertStringContainsString('<li>parent', $result);
        $this->assertStringContainsString('<li>child</li>', $result);
    }
}

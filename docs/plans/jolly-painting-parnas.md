# План: ограниченный Markdown для проекта

## Контекст

Сейчас весь пользовательский контент (комментарии) отображается как plain text с `whitespace-pre-wrap`. Нужно добавить поддержку ограниченного набора Markdown: **жирный**, *курсив*, `код`, ~~зачёркнутый~~, ссылки, списки, цитаты, блоки кода. Решение должно быть переиспользуемым по всему сайту.

## Подход

Парсинг Markdown на **бэкенде** через `league/commonmark` (уже есть в Laravel как зависимость). HTML генерируется на сервере, передаётся на фронт и рендерится через `v-html`. Это безопасно, т.к. HTML санитизируется на бэкенде.

## Шаги реализации

### 1. Создать `app/Services/MarkdownService.php`

Сервис-класс с методом `toHtml(string $markdown): string`:
- Использует `league/commonmark` `MarkdownConverter` с `CommonMarkCoreExtension` + `StrikethroughExtension` (из GFM)
- Конфигурация: `html_input` => `'strip'` (убирает сырой HTML из ввода), `allow_unsafe_links` => `false`
- После рендеринга — `strip_tags()` с разрешёнными тегами: `<p>`, `<br>`, `<strong>`, `<em>`, `<code>`, `<pre>`, `<del>`, `<a>`, `<ul>`, `<ol>`, `<li>`, `<blockquote>`
- Для ссылок `<a>`: добавить `rel="nofollow noopener noreferrer"` и `target="_blank"`, удалить опасные атрибуты (через простую регулярку)
- Заголовки (`<h1>`-`<h6>`) и изображения (`<img>`) не попадут в `strip_tags` — автоматически отфильтруются

### 2. Добавить accessor в модель `Comment`

В `app/Models/Comment.php` добавить accessor `message_html`, который вызывает `MarkdownService::toHtml($this->message)`.

### 3. Обновить `CommentController`

Во всех местах, где передаётся `'message' => $child->message`, добавить `'message_html' => $child->message_html`. Затронутые места:
- `show()` — массив `comment`
- `buildTreeFromCollection()` — два места внутри замыканий (с `maxDepth` и без)
- `loadMore()` — через `buildTreeFromCollection`

На главной странице (`index()`) комментарии отображаются обрезанными — там markdown не нужен.

### 4. Обновить Vue-компоненты

**`resources/js/Components/CommentItem.vue`**:
- Заменить `{{ comment.message }}` на `<div v-html="comment.message_html"></div>`
- Убрать `whitespace-pre-wrap` (markdown уже формирует блочные элементы)

### 5. Добавить панель markdown-кнопок в `CommentForm.vue`

Между полем «Имя» и textarea добавить toolbar с кнопками форматирования:
- **Ж** (жирный `**текст**`), **К** (курсив `*текст*`), **~~S~~** (зачёркнутый `~~текст~~`), **`<>`** (инлайн-код `` `код` ``), **```** (блок кода `` ```\nкод\n``` ``), **Ссылка** (`[текст](url)`), **Список** (`- элемент`), **Нум. список** (`1. элемент`), **Цитата** (`> текст`)

Логика работы:
- Textarea получает `ref` для доступа к DOM-элементу
- При нажатии кнопки: если текст выделен — оборачивает выделение соответствующими тегами; если нет — вставляет шаблон с плейсхолдером и выделяет его
- Реализовать как composable `resources/js/Composables/useMarkdownToolbar.js` с функцией `applyMarkdown(textareaRef, formMessageRef, markdownType)` — для переиспользования на других формах
- Использование `selectionStart`/`selectionEnd` для работы с выделением в textarea

### 6. Цитирование выделенного текста при ответе

В `CommentItem.vue`: при нажатии «Ответить», если в блоке комментария выделен текст — вставить его в форму ответа как цитату:
- При клике на «Ответить» проверить `window.getSelection()` — есть ли выделение внутри блока сообщения текущего комментария
- Если да — извлечь выделенный текст, преобразовать в markdown-цитату (каждая строка с префиксом `> `), вставить в `message` формы ответа с пустой строкой после цитаты
- `CommentForm` получает новый prop `initialMessage` — если передан, устанавливается в `form.message` при монтировании
- Если текст не выделен — форма открывается пустой (как сейчас)

### 7. Добавить CSS-стили для markdown-контента

В общий CSS (`resources/css/app.css`) или через Tailwind `@apply` добавить стили для элементов внутри `.markdown-content`:
- `p`, `ul`, `ol`, `blockquote`, `pre`, `code` — отступы, цвета, фон для кода

## Файлы

| Файл | Действие |
|---|---|
| `app/Services/MarkdownService.php` | Создать |
| `app/Models/Comment.php` | Изменить (accessor) |
| `app/Http/Controllers/CommentController.php` | Изменить (передавать `message_html`) |
| `resources/js/Components/CommentItem.vue` | Изменить (`v-html`, цитирование при ответе) |
| `resources/js/Components/CommentForm.vue` | Изменить (toolbar, `initialMessage` prop) |
| `resources/js/Composables/useMarkdownToolbar.js` | Создать |
| `resources/css/app.css` | Изменить (стили markdown) |

## Проверка

1. `docker compose exec php php artisan tinker` — проверить `app(\App\Services\MarkdownService::class)->toHtml('**bold** *italic* ~~strike~~')`
2. Создать комментарий с markdown-разметкой и убедиться, что он рендерится корректно
3. Убедиться, что сырой HTML в комментариях (`<script>alert(1)</script>`) стрипается
4. Проверить toolbar: выделить текст в textarea → нажать кнопку «Ж» → текст обернулся в `**...**`
5. Проверить цитирование: выделить текст в комментарии → нажать «Ответить» → в форме появилась цитата `> выделенный текст`
6. `docker compose exec php composer test` — убедиться, что существующие тесты проходят

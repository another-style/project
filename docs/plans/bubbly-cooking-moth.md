# Добавление тегов к темам

## Контекст

Пользователи хотят иметь возможность добавлять теги при создании тем (корневых комментариев). Теги отображаются в виде облака на главной странице и позволяют фильтровать темы. В админке нужен полноценный CRUD-ресурс для тегов.

## Архитектура

Теги реализуются через отдельную таблицу `tags` и связующую таблицу `comment_tag` (Many-to-Many). Без внешних пакетов — функционал достаточно простой.

## Шаги реализации

### 1. Миграция: таблицы `tags` и `comment_tag`

Файл: `database/migrations/2026_02_20_000001_create_tags_table.php`

- Таблица `tags`: `id`, `name` (string, 50, unique), `created_at`, `updated_at`
- Таблица `comment_tag`: `comment_id` (FK → comments.id, cascade), `tag_id` (FK → tags.id, cascade), unique(comment_id, tag_id)

### 2. Миграция: permissions для тегов

Файл: `database/migrations/2026_02_20_000002_create_tag_permissions.php`

Permissions: `Tag.view`, `Tag.create`, `Tag.update`, `Tag.delete` — по аналогии с `create_comment_permissions.php`.

### 3. Модель `Tag`

Файл: `app/Models/Tag.php`

- `fillable`: `name`
- Связь `comments()`: `BelongsToMany` → `Comment`

### 4. Обновить модель `Comment`

Файл: `app/Models/Comment.php`

- Добавить связь `tags()`: `BelongsToMany` → `Tag`

### 5. Обновить `CommentController`

Файл: `app/Http/Controllers/CommentController.php`

**`store()`**: добавить валидацию `tags` (массив строк, каждый max:50, max 5 тегов). Для каждого тега — `firstOrCreate` по `name`, затем `$comment->tags()->sync($tagIds)`. Теги добавляются только при создании корневого комментария (когда `parent_id` = null).

**`index()`**:
- Добавить eager loading `tags` для тем
- Передать `tags` с каждой темой в Inertia-ответ
- Получить все теги, у которых есть хотя бы одна тема, с подсчётом количества — передать как `allTags` для облака
- Добавить фильтрацию по query-параметру `tag`: `?tag=название`

### 6. Обновить `CommentForm.vue`

Файл: `resources/js/Components/CommentForm.vue`

- Добавить prop `showTags` (boolean, default false) — показывать поле тегов только в форме создания темы
- Добавить в `form` поле `tags: []` (массив строк)
- Добавить input для ввода тега: при нажатии Enter тег добавляется в массив (если не пустой и не дубликат), поле очищается
- Отображать добавленные теги как "чипсы" с кнопкой удаления
- Лимит: максимум 5 тегов
- При `onSuccess` — сбрасывать и `tags`

### 7. Обновить `Home.vue`

Файл: `resources/js/Pages/Home.vue`

- Принимать prop `allTags` (массив `{name, count}`) и `currentTag` (string|null)
- Под формой создания темы отобразить облако тегов
- Каждый тег — ссылка `/?tag=название`, активный тег выделен
- При наличии активного фильтра — показать кнопку «Сбросить фильтр» (ссылка на `/`)
- Отображать теги каждой темы в карточке темы (под текстом, перед лайками)

### 8. Filament: `TagResource`

Файлы:
- `app/Filament/Resources/TagResource.php`
- `app/Filament/Resources/TagResource/Pages/ListTags.php`
- `app/Filament/Resources/TagResource/Pages/EditTag.php`
- `app/Filament/Resources/TagResource/Pages/CreateTag.php`

Форма: `name` (TextInput, required, max 50).
Таблица: `id`, `name`, количество комментариев (withCount), `created_at`.
Действия: Edit, Delete, BulkDelete.

### 9. Обновить `CommentResource`

Файл: `app/Filament/Resources/CommentResource.php`

- Добавить колонку тегов в таблицу (через relationship)
- Добавить теги в форму редактирования (Select/TagsInput с множественным выбором)

### 10. `TagPolicy`

Файл: `app/Policies/TagPolicy.php`

По аналогии с `CommentPolicy` — методы `viewAny`, `view`, `create`, `update`, `delete`, `deleteAny`. Каждый проверяет `$user->isActive()` и соответствующий permission `Tag.*`.

## Файлы для изменения

- `database/migrations/2026_02_20_000001_create_tags_table.php` (новый)
- `database/migrations/2026_02_20_000002_create_tag_permissions.php` (новый)
- `app/Models/Tag.php` (новый)
- `app/Models/Comment.php` (изменение — добавить связь)
- `app/Http/Controllers/CommentController.php` (изменение — store, index)
- `resources/js/Components/CommentForm.vue` (изменение — поле тегов)
- `resources/js/Pages/Home.vue` (изменение — облако тегов, фильтр, теги в карточках)
- `app/Filament/Resources/TagResource.php` (новый)
- `app/Filament/Resources/TagResource/Pages/*.php` (новые)
- `app/Filament/Resources/CommentResource.php` (изменение — теги)
- `app/Policies/TagPolicy.php` (новый)

## Проверка

1. `docker compose exec php php artisan migrate` — миграции проходят без ошибок
2. Создать тему с тегами через форму на главной — теги сохраняются
3. Облако тегов отображается на главной, клик фильтрует темы
4. В админке `/admin` — ресурс «Теги» доступен, CRUD работает
5. В CommentResource видны теги комментария
6. `docker compose exec php composer test` — тесты проходят

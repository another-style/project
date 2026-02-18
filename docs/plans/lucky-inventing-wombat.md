# Ссылка на последний комментарий с главной страницы

## Контекст

На главной странице у каждой темы отображается «Последний ответ: дата». Пользователь хочет, чтобы эта дата была кликабельной ссылкой, ведущей на страницу темы с прокруткой к последнему комментарию.

## Подход

1. Добавить денормализованное поле `last_comment_id` в таблицу `comments` (аналогично `last_comment_at`).
2. На фронтенде сделать «Последний ответ: дата» ссылкой на страницу темы.
3. На странице темы (`Show.vue`) добавить `id`-атрибуты к комментариям и автопрокрутку к якорю из URL-хэша.

## Изменения

### 1. Миграция: добавить столбец `last_comment_id`

**Файл**: новая миграция

- Добавить `unsignedBigInteger('last_comment_id')->nullable()`
- Заполнить для существующих корневых комментариев: для каждого root найти последний по `created_at` потомок и записать его `id`. Если потомков нет — записать собственный `id`.

### 2. Модель Comment: обновление `last_comment_id` при создании ответа

**Файл**: `app/Models/Comment.php`

- В существующем `booted()` событии `created` — дополнительно обновлять `last_comment_id` у корневого комментария (ID нового комментария).
- Для новых корневых комментариев — `last_comment_id = $comment->id`.

### 3. Контроллер: передавать `last_comment_id` на фронтенд

**Файл**: `app/Http/Controllers/CommentController.php`

- В `index()` ничего менять не нужно — `paginate()` уже возвращает все поля, включая новое `last_comment_id`.

### 4. Фронтенд: ссылка на последний комментарий

**Файл**: `resources/js/Pages/Home.vue`

- «Последний ответ: дата» обернуть в `<Link>`, ведущий на `route('comments.show', topic.id) + '#comment-' + topic.last_comment_id`.
- Добавить `@click.stop` чтобы клик по ссылке не срабатывал как клик по блоку темы.

### 5. Якоря и прокрутка на странице темы

**Файл**: `resources/js/Components/CommentItem.vue`

- Добавить `:id="'comment-' + comment.id"` на корневой `<div>` комментария.

**Файл**: `resources/js/Pages/Comments/Show.vue`

- В `onMounted` — проверить `window.location.hash` и если есть, сделать `scrollIntoView` к соответствующему элементу.

### 6. Seeder

**Файл**: `database/seeders/CommentSeeder.php`

- При создании тредов дополнительно записывать `last_comment_id` (ID последнего ответа).

## Файлы для изменения

- Новая миграция — добавление столбца `last_comment_id`
- `app/Models/Comment.php` — обновление `last_comment_id` в событии `created`
- `resources/js/Pages/Home.vue` — ссылка на последний комментарий
- `resources/js/Components/CommentItem.vue` — добавить `id` атрибут
- `resources/js/Pages/Comments/Show.vue` — прокрутка к якорю
- `database/seeders/CommentSeeder.php` — заполнение `last_comment_id`

## Проверка

1. Запустить миграцию: `docker compose exec php php artisan migrate`
2. На главной странице убедиться, что «Последний ответ: дата» стал ссылкой
3. Кликнуть — должен открыться тред с прокруткой к последнему комментарию
4. Добавить новый ответ, вернуться на главную — ссылка должна вести на новый комментарий
5. Запустить тесты: `docker compose exec php composer test`

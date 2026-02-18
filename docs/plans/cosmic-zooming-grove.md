# План: Анонимная доска комментариев

## Контекст

Необходимо реализовать анонимную доску комментариев (по типу Двач/Пикабу/Reddit) с древовидной структурой. Пакет `kalnoy/nestedset` уже установлен. Комментарии анонимные — аутентификация не требуется. "Тема" = корневой комментарий (без родителя). Каждый комментарий имеет свой URL.

---

## Шаг 1. Миграция таблицы `comments`

**Файл:** `database/migrations/2026_02_18_000001_create_comments_table.php`

Поля:
- `id` — bigIncrements
- `_lft`, `_rgt`, `parent_id` — через `$table->nestedSet()`
- `name` — string, nullable (необязательное имя автора)
- `message` — text (до 5000 символов, валидация на уровне request)
- `ip_address` — string(45) (IPv4/IPv6)
- `timestamps()`
- `softDeletes()`

Индексы: `parent_id` (автоматически от nestedset), `created_at`.

## Шаг 2. Миграция permissions для Comment

**Файл:** `database/migrations/2026_02_18_000002_create_comment_permissions.php`

Permissions: `Comment.view`, `Comment.create`, `Comment.update`, `Comment.delete`.

Паттерн — как в существующих миграциях (вставка через `Permission::create`).

## Шаг 3. Модель `Comment`

**Файл:** `app/Models/Comment.php`

- Использует `NodeTrait` из kalnoy/nestedset, `SoftDeletes`, `HasFactory`
- `$fillable`: `name`, `message`, `ip_address`, `parent_id`
- Связь `parent()` и `children()` — предоставляются NodeTrait
- Scope для корневых комментариев (тем): `scopeRoots` — `whereNull('parent_id')`

## Шаг 4. Policy `CommentPolicy`

**Файл:** `app/Policies/CommentPolicy.php`

Методы: `viewAny`, `view`, `create`, `update`, `delete`, `deleteAny` — по паттерну `UserPolicy`. Каждый метод проверяет `$user->isActive()` + `$user->hasPermissionTo('Comment.action')`.

Регистрация Policy НЕ нужна в `AppServiceProvider` — модель в `App\Models`, Laravel найдёт автоматически.

## Шаг 5. Filament-ресурс `CommentResource`

**Файл:** `app/Filament/Resources/CommentResource.php`
**Pages:** `ListComments`, `EditComment` (без CreateComment — комментарии создаются только на фронтенде)

Таблица (простой список, не дерево):
- `id`
- `name` (или "Аноним" если пустое)
- `message` (обрезанный до ~100 символов)
- `ip_address`
- `parent_id` (ссылка на родителя или "Тема" если null)
- `created_at`

Actions: `DeleteAction` (soft delete), `RestoreAction`, `ForceDeleteAction`.
Фильтры: `TrashedFilter` для просмотра удалённых.

## Шаг 6. Контроллер `CommentController`

**Файл:** `app/Http/Controllers/CommentController.php`

Методы:
- `index()` — главная страница, корневые комментарии (темы) с пагинацией, отсортированные по дате (новые первые). Рендерит `Inertia::render('Home')`.
- `show(Comment $comment)` — страница конкретного комментария с деревом ответов. Загружает потомков до 10 уровней вложенности. Рендерит `Inertia::render('Comments/Show')`.
- `store(Request $request)` — создание комментария (может быть корневым или ответом). Валидация: `name` — nullable, max:50; `message` — required, max:5000; `parent_id` — nullable, exists:comments,id. Сохраняет IP через `$request->ip()`.
- `loadMore(Comment $comment)` — JSON-ответ для подгрузки потомков глубже 10 уровней.

## Шаг 7. Маршруты

**Файл:** `routes/web.php`

```php
Route::get('/', [CommentController::class, 'index'])->name('home');
Route::get('/comments/{comment}', [CommentController::class, 'show'])->name('comments.show');
Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
Route::get('/comments/{comment}/replies', [CommentController::class, 'loadMore'])->name('comments.replies');
```

Существующий маршрут `'/'` будет заменён — сейчас он рендерит `Welcome`.

## Шаг 8. Vue-компоненты (фронтенд)

### 8.1 Страница `Home.vue`
**Файл:** `resources/js/Pages/Home.vue`

- Layout: `GuestLayout` (анонимная доска)
- Список корневых комментариев (тем) с пагинацией
- Форма создания новой темы (name, message)
- Ссылка на каждую тему ведёт на `comments.show`

### 8.2 Страница `Comments/Show.vue`
**Файл:** `resources/js/Pages/Comments/Show.vue`

- Отображение корневого комментария
- Древовидная структура ответов (до 10 уровней)
- Кнопка "Показать ещё" для подгрузки ответов глубже 10 уровней (AJAX через `loadMore`)
- Форма ответа на любой комментарий

### 8.3 Компонент `CommentTree.vue`
**Файл:** `resources/js/Components/CommentTree.vue`

- Рекурсивный компонент для отображения дерева комментариев
- Принимает props: `comments` (массив), `depth` (текущая глубина), `maxDepth` (10)
- На глубине > maxDepth показывает кнопку "Показать ещё"

### 8.4 Компонент `CommentItem.vue`
**Файл:** `resources/js/Components/CommentItem.vue`

- Отображение одного комментария: имя (или "Аноним"), сообщение, дата
- Кнопка "Ответить" — раскрывает форму ответа
- Ссылка на отдельную страницу комментария

### 8.5 Компонент `CommentForm.vue`
**Файл:** `resources/js/Components/CommentForm.vue`

- Форма: поле name (необязательное), поле message (обязательное)
- Отправка через Inertia `router.post`

---

## Порядок реализации

1. Миграции (comments table + permissions)
2. Модель Comment
3. CommentPolicy
4. Filament CommentResource + Pages
5. CommentController
6. Маршруты
7. Vue-компоненты (Home, Show, CommentTree, CommentItem, CommentForm)

## Верификация

1. `docker compose exec php php artisan migrate` — миграции проходят
2. `docker compose exec php php artisan test` — существующие тесты не сломаны
3. Админка `/admin/comments` — список комментариев, soft delete работает
4. Главная `/` — список тем, создание новой темы
5. `/comments/{id}` — дерево комментариев, ответы, подгрузка глубоких уровней

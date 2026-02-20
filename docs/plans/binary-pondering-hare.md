# Закрепление комментариев (pin)

## Контекст

Администратору нужна возможность закреплять комментарии (темы) через админку Filament. Закреплённые комментарии должны отображаться первыми на главной странице. Авторизация — через отдельный permission `Comment.pin`.

## Изменения

### 1. Миграция: добавить поле `is_pinned`

**Файл:** `database/migrations/2026_02_20_100000_add_is_pinned_to_comments_table.php`

- Добавить `boolean('is_pinned')->default(false)` в таблицу `comments`
- Добавить индекс на `is_pinned` (для сортировки)

### 2. Миграция: создать permission `Comment.pin`

**Файл:** `database/migrations/2026_02_20_100001_create_comment_pin_permission.php`

- Создать permission `Comment.pin` по аналогии с существующими миграциями permissions

### 3. Модель `Comment`

**Файл:** `app/Models/Comment.php`

- Добавить `is_pinned` в `$fillable`
- Добавить `'is_pinned' => 'boolean'` в `casts()`

### 4. Policy: метод `pin`

**Файл:** `app/Policies/CommentPolicy.php`

- Добавить метод `pin(User $user, Comment $comment): bool` — проверяет `$user->isActive()` и permission `Comment.pin`

### 5. Filament `CommentResource`

**Файл:** `app/Filament/Resources/CommentResource.php`

- **Таблица:** добавить колонку `IconColumn::make('is_pinned')` с иконкой булавки
- **Таблица:** добавить действие (action) «Закрепить/Открепить» — toggle `is_pinned`, авторизованное через `Comment.pin` (вызов `$this->authorize('pin', $record)` или проверка `auth()->user()->can('pin', $record)`)
- **Форма:** добавить `Toggle::make('is_pinned')` с label «Закреплён»
- **Фильтр:** добавить фильтр по закреплённым комментариям

### 6. Контроллер: сортировка на главной

**Файл:** `app/Http/Controllers/CommentController.php`

В методе `index()` изменить сортировку: сначала `orderByDesc('is_pinned')`, затем `orderByDesc('last_comment_at')`. Закреплённые комментарии всегда будут вверху списка.

### 7. Фронтенд: визуальная метка

**Файл:** `resources/js/Pages/Home.vue`

- Добавить визуальный индикатор (иконка булавки / бейдж «Закреплён») для тем с `is_pinned: true`
- Данные `is_pinned` передать через Inertia props (добавить в select/toArray)

## Файлы для изменения

| Файл | Действие |
|------|----------|
| `database/migrations/2026_02_20_100000_add_is_pinned_to_comments_table.php` | Создать |
| `database/migrations/2026_02_20_100001_create_comment_pin_permission.php` | Создать |
| `app/Models/Comment.php` | Изменить |
| `app/Policies/CommentPolicy.php` | Изменить |
| `app/Filament/Resources/CommentResource.php` | Изменить |
| `app/Http/Controllers/CommentController.php` | Изменить |
| `resources/js/Pages/Home.vue` | Изменить |

## Проверка

1. Запустить миграции: `docker compose exec php php artisan migrate`
2. Назначить permission `Comment.pin` нужной роли через админку
3. Проверить в админке: появление колонки, действие закрепления/открепления, toggle в форме редактирования
4. Проверить на главной: закреплённый комментарий отображается первым с визуальной меткой
5. Запустить тесты: `docker compose exec php composer test`

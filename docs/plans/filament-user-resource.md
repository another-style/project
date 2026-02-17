# План: Filament-ресурс «Пользователи» + поле активности

## Контекст
Нужно добавить управление пользователями в админ-панель Filament: список и редактирование. Также добавить булево поле `is_active` в таблицу `users` с миграцией.

## Шаги реализации

### 1. Миграция — добавить поле `is_active`
- Создать миграцию `add_is_active_to_users_table`
- Добавить `boolean('is_active')->default(true)` после поля `password`
- `database/migrations/2026_02_17_XXXXXX_add_is_active_to_users_table.php`

### 2. Обновить модель `User`
- **Файл**: `app/Models/User.php`
- Добавить `is_active` в `$fillable`
- Добавить каст `'is_active' => 'boolean'`

### 3. Обновить `UserFactory`
- **Файл**: `database/factories/UserFactory.php`
- Добавить `'is_active' => true` в `definition()`

### 4. Создать Filament-ресурс `UserResource`
- **Файл**: `app/Filament/Resources/UserResource.php`

**Таблица (список)**:
- `name` — текстовый столбец, searchable
- `email` — текстовый столбец, searchable
- `is_active` — иконка (boolean)
- `created_at` — дата, sortable

**Форма (редактирование)**:
- `name` — TextInput, required
- `email` — TextInput, email, required, unique (кроме текущего)
- `password` — TextInput, password, required только при создании, при редактировании необязательное (если пустое — не обновлять)
- `is_active` — Toggle

**Страницы**:
- `ListUsers` — список
- `CreateUser` — создание
- `EditUser` — редактирование

### 5. Создать страницы ресурса
- `app/Filament/Resources/UserResource/Pages/ListUsers.php`
- `app/Filament/Resources/UserResource/Pages/CreateUser.php`
- `app/Filament/Resources/UserResource/Pages/EditUser.php`

## Файлы для изменения/создания
- `app/Models/User.php` — изменить
- `database/factories/UserFactory.php` — изменить
- `database/migrations/2026_02_17_*_add_is_active_to_users_table.php` — создать
- `app/Filament/Resources/UserResource.php` — создать
- `app/Filament/Resources/UserResource/Pages/ListUsers.php` — создать
- `app/Filament/Resources/UserResource/Pages/CreateUser.php` — создать
- `app/Filament/Resources/UserResource/Pages/EditUser.php` — создать

## Проверка
- Выполнить миграцию: `docker compose exec php php artisan migrate`
- Открыть `/admin` → раздел «Users» должен появиться в навигации
- Проверить список, создание и редактирование пользователя
- Убедиться, что toggle `is_active` работает
- Запустить тесты: `docker compose exec php composer test`

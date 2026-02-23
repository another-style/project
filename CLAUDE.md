# CLAUDE.md

Этот файл содержит инструкции для Claude Code (claude.ai/code) при работе с кодом в этом репозитории.

## Общие принципы работы и взаимодействие с пользователем

* Общайся с пользователем на русском языке.
* Если ты не знаешь ответ на вопрос или не знаешь как решить задачу, то не придумывай ничего, скажи просто "Я не знаю".
* Никогда не удаляй и не изменяй комментарии в PHP-коде, если того не требуется в рамках решения задачи.
  Запрещается удалять комментарии относительно тех мест кода, в которых не было изменений.
* При ссылках на код в этом файле (CLAUDE.md) **никогда не указывай номера строк** — они устаревают при изменении кода. Вместо этого используй полное имя класса и метода в формате PHP: `\App\Models\User::isActive`. Если нужно сослаться на файл без конкретного метода, указывай только путь: `app/Models/User.php`.
* Никогда не выполняй git-команды, которые изменяют состояние репозитория: `git add`, `git commit`, `git push`, `git merge`, `git rebase`, `git reset`, `git checkout`, `git stash`, `git branch -d` и т.д. Разрешены только команды для чтения: `git status`, `git log`, `git diff`, `git show`, `git branch` (без -d/-D).
* Никогда не удаляй файл `todo.txt` в корне проекта.
* Если в php-файлах есть неиспользуемые импорты классов через ключевое слово `use`, то удаляй их.

## Обзор проекта

Приложение на Laravel 12 с двойным фронтендом: Inertia.js + Vue 3 для публичных страниц, Filament 3 для админ-панели (`/admin`). Используется spatie/laravel-permission для ролей и прав доступа. Аутентификация реализована через Laravel Breeze.

## Docker-окружение

Оркестрация через `docker-compose.yml`: PHP-FPM, Nginx (порт 8000), MySQL 8 (порт 3308), Redis (порт 6379), Node (порт 5173, только профиль dev).

```bash
# Запуск сервисов
docker compose up -d

# Запуск с Vite dev-сервером
docker compose --profile dev up -d

# Выполнение artisan-команд
docker compose exec php php artisan <команда>

# Выполнение composer-команд
docker compose exec php composer <команда>
```

## Основные команды

```bash
# Полная настройка проекта (composer install, key:generate, migrate, npm install, npm build)
composer setup

# Разработка (запускает server, queue, pail logs, vite параллельно)
composer dev

# Запуск всех тестов (очищает конфиг, использует SQLite in-memory)
composer test

# Запуск одного тестового файла
php artisan test --filter=ProfileTest

# Запуск одного тестового метода
php artisan test --filter=ProfileTest::test_profile_page_is_displayed

# Линтинг/форматирование PHP-кода
./vendor/bin/pint

# Сборка фронтенд-ассетов
npm run build

# Vite dev-сервер
npm run dev

# Генерация PHPDoc для моделей (barryvdh/laravel-ide-helper)
# Флаг -W перезаписывает PHPDoc прямо в файле модели
# ВАЖНО: всегда запускай эту команду после изменения модели
# (добавление/удаление полей, связей, аксессоров, скоупов и т.д.)
docker compose exec php php artisan ide-helper:models -W "\App\Models\{Model}"
```

## Архитектура

### Бэкенд (Laravel)
- **Маршруты**: `routes/web.php` (Inertia-страницы), `routes/auth.php` (маршруты аутентификации Breeze). Filament автоматически регистрирует свои маршруты под `/admin`.
- **Контроллеры**: `app/Http/Controllers/` — стандартные контроллеры возвращают ответы через `Inertia::render()`.
- **Middleware**: `HandleInertiaRequests` передаёт `auth.user` prop на все Inertia-страницы.
- **Модели**: `app/Models/` — Eloquent-модели с трейтами spatie permission. Модель `Comment` использует `kalnoy/nestedset` (`NodeTrait`) для иерархической структуры комментариев и `SoftDeletes`.

### Фронтенд (Vue 3 + Inertia)
- **Точка входа**: `resources/js/app.js`
- **Страницы**: `resources/js/Pages/` — Vue SFC, соответствующие 1:1 маршрутам Inertia (например, `Dashboard.vue` отображается на `/dashboard`).
- **Лейауты**: `resources/js/Layouts/` — `AuthenticatedLayout.vue`, `GuestLayout.vue`.
- **Компоненты**: `resources/js/Components/` — переиспользуемые UI-компоненты (кнопки, поля ввода, модалки).
- **Маршрутизация**: Ziggy предоставляет именованные Laravel-маршруты в JS через хелпер `route()`.
- **Стили**: Tailwind CSS с плагином `@tailwindcss/forms`.

### Админ-панель (Filament)
- Filament использует Livewire + Alpine.js, сосуществует с Inertia+Vue без конфликтов.
- Ресурсы, страницы и виджеты размещаются в `app/Filament/`.
- **Существующие ресурсы**: `UserResource`, `TagResource`, `RoleResource`, `PermissionResource` (группа навигации «Доступ»), `CommentResource` (модерация комментариев с поддержкой soft delete, включая кастомное действие `togglePin` через permission `Comment.pin`).

### Роли и разрешения (spatie/laravel-permission)
- Модель `User` использует трейт `HasRoles` и реализует интерфейс `FilamentUser`.
- Доступ в админ-панель контролируется через permission `AdminPanel.access` в методе `\App\Models\User::canAccessPanel`.
- **Формат permissions**: `Entity.action`, где `Entity` — название сущности (PascalCase), `action` — действие (lowercase). Примеры: `User.view`, `User.create`, `Role.update`, `Permission.delete`, `Comment.pin`, `CommentImage.delete`, `AdminPanel.access`.
- При добавлении нового Filament-ресурса для сущности необходимо создать соответствующие permissions в отдельной миграции.

### Авторизация действий (Policy)
- Policy-классы расположены в `app/Policies/` и используются Filament для авторизации CRUD-действий над ресурсами.
- Для моделей из `App\Models` Laravel обнаруживает Policy автоматически (например, `UserPolicy` для `User`).
- Для моделей spatie (`Spatie\Permission\Models\Role`, `Spatie\Permission\Models\Permission`) Policy регистрируются явно через `Gate::policy()` в `\App\Providers\AppServiceProvider::boot`.
- **Существующие Policy**: `UserPolicy`, `CommentPolicy` (дополнительно содержит метод `pin` → `Comment.pin`), `TagPolicy`, `CommentImagePolicy` (только `viewAny`/`delete`/`deleteAny` → `CommentImage.delete`), `RolePolicy`, `PermissionPolicy`.
- **Проверка активности пользователя**: каждый метод Policy обязательно проверяет `$user->isActive()` перед проверкой permissions. Заблокированный пользователь (`is_active = false`) не может выполнять никакие действия, даже если у него есть соответствующие permissions. Метод `\App\Models\User::isActive` возвращает значение поля `is_active`. Аналогично, `\App\Models\User::canAccessPanel` также проверяет активность пользователя перед предоставлением доступа к админ-панели.
- Каждый Policy содержит стандартный набор методов, которые Filament вызывает автоматически:

| Метод Policy   | Действие Filament       | Permission          |
|----------------|-------------------------|---------------------|
| `viewAny`      | Список / навигация      | `Entity.view`       |
| `view`         | Просмотр записи         | `Entity.view`       |
| `create`       | Создание записи         | `Entity.create`     |
| `update`       | Редактирование записи   | `Entity.update`     |
| `delete`       | Удаление записи         | `Entity.delete`     |
| `deleteAny`    | Массовое удаление       | `Entity.delete`     |

- **При добавлении нового Filament-ресурса** необходимо: (1) создать permissions в миграции, (2) создать Policy-класс с методами из таблицы выше (каждый метод должен проверять `$user->isActive()` перед проверкой permission), (3) если модель не из `App\Models` — зарегистрировать Policy в `AppServiceProvider`.

### Сервисы (app/Services/)

- **`MarkdownService`** — конвертирует Markdown в безопасный HTML через League\CommonMark (расширения CommonMark + Strikethrough). После конвертации стрипает теги, оставляя только разрешённые (`p`, `pre`, `ul`, `ol`, `li`, `blockquote`, `h1–h6`, `hr`, `br`, `strong`, `em`, `code`, `del`). Используется в аксессоре `\App\Models\Comment::messageHtml`.
- **`CommentImageService`** — сохраняет загруженные изображения в `storage/app/public/comment-images/`. Имя файла = `md5(содержимого).расширение`, каталог — три вложенных уровня из первых символов имени файла (например, `d2d8f9c2.jpg` → `d/2/d/d2d8f9c2.jpg`). Повторный upload одного файла не создаёт дубликат (через `firstOrCreate`).

### Тестирование
- PHPUnit с двумя наборами: `tests/Unit/` и `tests/Feature/`.
- Тесты используют базу данных SQLite `:memory:` (настроено в `phpunit.xml`).

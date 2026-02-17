# CLAUDE.md

Этот файл содержит инструкции для Claude Code (claude.ai/code) при работе с кодом в этом репозитории.

## Общие принципы работы и взаимодействие с пользователем

* Общайся с пользователем на русском языке.
* Если ты не знаешь ответ на вопрос или не знаешь как решить задачу, то не придумывай ничего, скажи просто "Я не знаю".
* Никогда не удаляй и не изменяй комментарии в PHP-коде, если того не требуется в рамках решения задачи.
  Запрещается удалять комментарии относительно тех мест кода, в которых не было изменений.
* При ссылках на код в этом файле (CLAUDE.md) **никогда не указывай номера строк** — они устаревают при изменении кода. Вместо этого используй полное имя класса и метода в формате PHP: `\App\JsonApi\V1\Employees\Adapter::validateUniqueEmployee`. Если нужно сослаться на файл без конкретного метода, указывай только путь: `app/JsonApi/V1/Employees/Adapter.php`.
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
```

## Архитектура

### Бэкенд (Laravel)
- **Маршруты**: `routes/web.php` (Inertia-страницы), `routes/auth.php` (маршруты аутентификации Breeze). Filament автоматически регистрирует свои маршруты под `/admin`.
- **Контроллеры**: `app/Http/Controllers/` — стандартные контроллеры возвращают ответы через `Inertia::render()`.
- **Middleware**: `HandleInertiaRequests` передаёт `auth.user` prop на все Inertia-страницы.
- **Модели**: `app/Models/` — Eloquent-модели с трейтами spatie permission.

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

### Тестирование
- PHPUnit с двумя наборами: `tests/Unit/` и `tests/Feature/`.
- Тесты используют базу данных SQLite `:memory:` (настроено в `phpunit.xml`).

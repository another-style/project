# Установка и запуск проекта

## Требования

- [Docker](https://docs.docker.com/get-docker/) и [Docker Compose](https://docs.docker.com/compose/install/) (Docker Desktop для Windows/Mac уже включает Compose)
- Git

Никаких локальных установок PHP, Node.js или MySQL не нужно — всё работает в контейнерах.

## Быстрый старт

### 1. Клонировать репозиторий

```bash
git clone <url-репозитория>
cd project
```

### 2. Создать файл окружения

```bash
cp .env.example .env
```

Файл `.env.example` уже содержит все настройки для Docker. Менять ничего не нужно.

### 3. Собрать и запустить контейнеры

```bash
docker compose up -d --build
```

Это запустит 4 контейнера:

| Контейнер | Назначение | Порт |
|---|---|---|
| project-php | PHP 8.2 FPM | 9000 (внутренний) |
| project-nginx | Веб-сервер | **8000** |
| project-mysql | База данных MySQL 8 | 3308 |
| project-redis | Кэш и сессии | 6379 |

### 4. Установить PHP-зависимости

```bash
docker compose exec php composer install
```

### 5. Сгенерировать ключ приложения

```bash
docker compose exec php php artisan key:generate
```

### 6. Запустить миграции

```bash
docker compose exec php php artisan migrate
```

### 7. Установить npm-зависимости и собрать фронтенд

```bash
docker compose run --rm node sh -c "cd /var/www && npm install"
docker compose run --rm node sh -c "cd /var/www && npm run build"
```

### 8. Готово

Откройте в браузере:

- **Сайт:** http://localhost:8000
- **Админ-панель (Filament):** http://localhost:8000/admin

## Разработка фронтенда

Для hot-reload при работе с Vue-компонентами запустите Vite dev server:

```bash
docker compose --profile dev up node
```

Vite будет доступен на порту 5173 и автоматически обновлять страницу при изменении файлов.

Для остановки нажмите `Ctrl+C` или в другом терминале:

```bash
docker compose --profile dev stop node
```

## Полезные команды

```bash
# Остановить все контейнеры
docker compose down

# Остановить контейнеры и удалить данные БД
docker compose down -v

# Зайти в PHP-контейнер (для artisan-команд)
docker compose exec php bash

# Выполнить artisan-команду
docker compose exec php php artisan <команда>

# Посмотреть логи
docker compose logs -f

# Пересобрать контейнеры (после изменения Dockerfile)
docker compose up -d --build
```

## Возможные проблемы

**Порт 3308 (или 8000) уже занят**

Если при запуске контейнеров появляется ошибка `port is already allocated`, измените порт в `docker-compose.yml`. Например, для MySQL замените `"3308:3306"` на `"3309:3306"`. Внутренние настройки в `.env` менять не нужно — контейнеры общаются по внутренней сети Docker.

**`npm install` выдаёт ошибку прав доступа**

```bash
docker compose exec -u root php chown -R $(id -u):$(id -g) .
```

**MySQL не успел запуститься**

При первом запуске MySQL может инициализироваться несколько секунд. Если миграции упали с ошибкой подключения, подождите 10-15 секунд и повторите:

```bash
docker compose exec php php artisan migrate
```

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

| Сервис | Назначение | Порт по умолчанию |
|---|---|---|
| php | PHP 8.2 FPM | 9000 (внутренний) |
| nginx | Веб-сервер | **8000** |
| mysql | База данных MySQL 8 | 3308 |
| redis | Кэш и сессии | 6379 |

### 4. Установить PHP-зависимости

```bash
docker compose exec php composer install
```

### 5. Сгенерировать ключ приложения

```bash
docker compose exec php php artisan key:generate
```

### 6. Запустить миграции и сиды

```bash
docker compose exec php php artisan migrate --seed
```

### 7. Установить npm-зависимости и собрать фронтенд

```bash
docker compose run --rm node sh -c "cd /var/www && npm install && npm run build"
```

### 8. Готово

Откройте в браузере:

- **Сайт:** http://localhost:8000
- **Админ-панель (Filament):** http://localhost:8000/admin

### Краткий вариант (шаги 2–7 одной командой)

```bash
cp .env.example .env \
  && docker compose up -d --build \
  && docker compose exec php composer install \
  && docker compose exec php php artisan key:generate \
  && docker compose exec php php artisan migrate --seed \
  && docker compose run --rm node sh -c "cd /var/www && npm install && npm run build"
```

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

## Сброс базы данных

Откатить все миграции, накатить заново и запустить сиды:

```bash
docker compose exec php php artisan migrate:fresh --seed
```

## Полная остановка и очистка

Остановить контейнеры, удалить volumes (данные MySQL) и локально собранные образы:

```bash
docker compose --profile dev down -v --rmi local
```

> Флаг `--profile dev` — чтобы также удалился Node-контейнер.
> Флаг `-v` удаляет volumes (данные MySQL будут потеряны).
> Флаг `--rmi local` удаляет локально собранные образы (php, node), но не базовые (mysql, nginx, redis).

Если нужно удалить вообще всё, включая скачанные базовые образы:

```bash
docker compose --profile dev down -v --rmi all
```

После очистки можно повторить шаги из раздела «Быстрый старт» для развёртывания с нуля.

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

**Порт уже занят (`port is already allocated`)**

Если при запуске контейнеров появляется ошибка `port is already allocated`, значит указанный порт уже используется другим процессом или проектом. Порты настраиваются через переменные в файле `.env`:

| Переменная | Сервис | Значение по умолчанию |
|---|---|---|
| `NGINX_PORT` | Веб-сервер | 8000 |
| `MYSQL_PORT` | MySQL | 3308 |
| `REDIS_PORT` | Redis | 6379 |
| `VITE_PORT` | Vite dev server | 5173 |

Например, если порт 8000 занят, откройте `.env` и измените:

```
NGINX_PORT=8001
```

После этого перезапустите контейнеры: `docker compose up -d`. Внутренние настройки подключения между контейнерами менять не нужно — они общаются по внутренней сети Docker.

**`npm install` выдаёт ошибку прав доступа**

```bash
docker compose exec -u root php chown -R $(id -u):$(id -g) .
```

**MySQL не успел запуститься**

При первом запуске MySQL может инициализироваться несколько секунд. Если миграции упали с ошибкой подключения, подождите 10-15 секунд и повторите:

```bash
docker compose exec php php artisan migrate --seed
```

## Установка MCP сервера

Для подключения Claude Code к тестовой СУБД используется MCP сервер MySQL.

Вендор: https://github.com/benborla/mcp-server-mysql

### 1. Установить Node.js (если не установлен)

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2. Установить MCP сервер

```bash
npm install -g @benborla29/mcp-server-mysql
```

### 3. Подключить MCP сервер к Claude Code

```bash
claude mcp add mcp_server_mysql \
  -e MYSQL_HOST="localhost" \
  -e MYSQL_PORT="3308" \
  -e MYSQL_USER="project" \
  -e MYSQL_PASS="secret" \
  -e MYSQL_DB="project" \
  -- npx @benborla29/mcp-server-mysql
```

# Деплой на production-сервер

## Требования к серверу (VPS)

- **OS**: Ubuntu 22.04 / 24.04 LTS
- **RAM**: 2 ГБ минимум (MySQL 8 ~400-500 МБ + PHP-FPM + Redis + Nginx)
- **CPU**: 1-2 ядра для старта
- **Диск**: 20+ ГБ SSD
- **Docker** и **Docker Compose** установлены на сервере

## Подготовка сервера

### 1. Установка Docker

```bash
# Обновить пакеты
sudo apt update && sudo apt upgrade -y

# Установить Docker
curl -fsSL https://get.docker.com | sudo sh

# Добавить текущего пользователя в группу docker (чтобы не писать sudo)
sudo usermod -aG docker $USER

# Перелогиниться, чтобы группа применилась
exit
# ... заново подключиться по SSH

# Проверить
docker --version
docker compose version
```

### 2. Установка Git

```bash
sudo apt install -y git
```

## Первый деплой

### 1. Клонировать репозиторий

```bash
git clone <url-репозитория> /var/www/project
cd /var/www/project
```

> Здесь и далее `/var/www/project` — пример пути. Используйте любую удобную директорию.
> Все последующие команды предполагают, что вы находитесь в корне проекта.

### 2. Настроить .env

```bash
cp .env.example .env
nano .env
```

Изменить следующие значения:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://твой-домен.ru

LOG_LEVEL=error

DB_DATABASE=project
DB_USERNAME=project
DB_PASSWORD=сложный_пароль_для_бд
DB_ROOT_PASSWORD=сложный_пароль_root

REDIS_PASSWORD=сложный_пароль_для_redis

SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

MAIL_MAILER=smtp
MAIL_HOST=smtp.твой-провайдер.ru
MAIL_PORT=587
MAIL_USERNAME=почта@твой-домен.ru
MAIL_PASSWORD=пароль_от_почты
MAIL_FROM_ADDRESS=почта@твой-домен.ru
```

> **DB_ROOT_PASSWORD** и **REDIS_PASSWORD** — новые переменные, которых нет в .env.example.
> Они используются в docker-compose.prod.yml.

### 3. Собрать фронтенд-ассеты

Есть три варианта доставки собранных ассетов на сервер:

#### Вариант А: Сборка на сервере через Docker-контейнер (рекомендуется)

Одноразовый контейнер — Node.js не ставится на хост, используется только на момент сборки:

```bash
docker run --rm -v $(pwd):/var/www -w /var/www node:20-alpine sh -c "npm install && npm run build"
```

#### Вариант Б: Сборка на сервере с установленным Node.js

Установить Node.js на сервер (один раз):

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs
```

Затем собирать ассеты напрямую:

```bash
npm install
npm run build
```

> Проще варианта А, но Node.js остаётся установленным на сервере постоянно.

#### Вариант В: Сборка локально + копирование на сервер

Собрать на локальной машине и скопировать по SSH:

```bash
# Локально
npm install
npm run build

# Скопировать на сервер
scp -r public/build/ user@сервер:/var/www/project/public/build/
```

> **Примечание к вариантам А и Б:** `npm install` скачает ~100-200 МБ зависимостей в `node_modules`,
> а сборка нагрузит CPU. На VPS с 2 ГБ RAM это нормально, на 1 ГБ может быть медленно.

### 4. Запустить контейнеры

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

### 5. Установить зависимости и настроить Laravel

```bash
# Установить PHP-зависимости (без dev)
docker compose -f docker-compose.prod.yml exec php composer install --no-dev --optimize-autoloader

# Сгенерировать ключ приложения
docker compose -f docker-compose.prod.yml exec php php artisan key:generate

# Запустить миграции
docker compose -f docker-compose.prod.yml exec php php artisan migrate --force

# Заполнить базу начальными данными (роли, permissions, администратор)
docker compose -f docker-compose.prod.yml exec php php artisan db:seed --force

# Кэшировать конфигурацию для производительности
docker compose -f docker-compose.prod.yml exec php php artisan config:cache
docker compose -f docker-compose.prod.yml exec php php artisan route:cache
docker compose -f docker-compose.prod.yml exec php php artisan view:cache
```

### 6. Настроить права на директории

```bash
docker compose -f docker-compose.prod.yml exec php chmod -R 775 storage bootstrap/cache
```

## Последующие деплои (обновление кода)

```bash
# Забрать свежий код
git pull

# Пересобрать контейнеры (если менялся Dockerfile)
docker compose -f docker-compose.prod.yml up -d --build

# Обновить зависимости
docker compose -f docker-compose.prod.yml exec php composer install --no-dev --optimize-autoloader

# Запустить новые миграции
docker compose -f docker-compose.prod.yml exec php php artisan migrate --force

# Обновить кэши
docker compose -f docker-compose.prod.yml exec php php artisan config:cache
docker compose -f docker-compose.prod.yml exec php php artisan route:cache
docker compose -f docker-compose.prod.yml exec php php artisan view:cache
```

Пересобрать фронтенд-ассеты (если были изменения во Vue-компонентах или стилях):

```bash
# Вариант А: через Docker-контейнер
docker run --rm -v $(pwd):/var/www -w /var/www node:20-alpine sh -c "npm install && npm run build"

# Вариант Б: если Node.js установлен на сервере
npm install && npm run build

# Вариант В: сборка локально + копирование на сервер
# (выполнить на локальной машине)
# npm run build
# scp -r public/build/ user@сервер:/var/www/project/public/build/
```

## SSL-сертификат (HTTPS)

### Вариант 1: Let's Encrypt + Certbot (бесплатно)

Установить Certbot на хост-машину (не в Docker):

```bash
sudo apt install -y certbot

# Остановить Nginx-контейнер на время получения сертификата
docker compose -f docker-compose.prod.yml stop nginx

# Получить сертификат
sudo certbot certonly --standalone -d твой-домен.ru -d www.твой-домен.ru

# Запустить Nginx обратно
docker compose -f docker-compose.prod.yml start nginx
```

Сертификаты будут в `/etc/letsencrypt/live/твой-домен.ru/`. Нужно примонтировать их в Nginx-контейнер и настроить конфиг Nginx для HTTPS.

Добавить в `docker-compose.prod.yml` для nginx:

```yaml
volumes:
  - /etc/letsencrypt:/etc/letsencrypt:ro
```

Автообновление сертификата (добавить в crontab на хосте):

```bash
sudo crontab -e
# Добавить строку:
0 3 * * * certbot renew --pre-hook "docker compose -f /var/www/project/docker-compose.prod.yml stop nginx" --post-hook "docker compose -f /var/www/project/docker-compose.prod.yml start nginx"
```

### Вариант 2: Cloudflare (проще)

1. Перенести DNS домена на Cloudflare
2. Включить проксирование (оранжевое облако)
3. В настройках SSL выбрать "Flexible" или "Full"
4. Cloudflare сам обеспечит HTTPS для посетителей

## Бэкапы базы данных

Создать скрипт `/var/www/project/backup.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/var/www/backups"
DATE=$(date +%Y-%m-%d_%H-%M)
mkdir -p $BACKUP_DIR

docker compose -f /var/www/project/docker-compose.prod.yml exec -T mysql \
  mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  > "$BACKUP_DIR/db_$DATE.sql"

# Удалить бэкапы старше 30 дней
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
```

Добавить в crontab:

```bash
crontab -e
# Ежедневный бэкап в 2:00
0 2 * * * /bin/bash /var/www/project/backup.sh
```

## Чеклист перед выкаткой

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Сложные пароли для MySQL и Redis
- [ ] Порты MySQL и Redis не проброшены наружу
- [ ] SSL-сертификат настроен
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_ENCRYPT=true`
- [ ] Фронтенд-ассеты собраны (`npm run build`)
- [ ] Composer-зависимости без dev (`--no-dev`)
- [ ] Кэши Laravel созданы (`config:cache`, `route:cache`, `view:cache`)
- [ ] Бэкапы настроены
- [ ] `restart: unless-stopped` у всех сервисов

## Полезные команды для мониторинга

```bash
# Статус контейнеров
docker compose -f docker-compose.prod.yml ps

# Логи всех сервисов
docker compose -f docker-compose.prod.yml logs

# Логи конкретного сервиса (последние 100 строк, в реальном времени)
docker compose -f docker-compose.prod.yml logs -f --tail=100 php

# Логи Laravel
docker compose -f docker-compose.prod.yml exec php tail -f storage/logs/laravel.log

# Зайти в PHP-контейнер
docker compose -f docker-compose.prod.yml exec php bash

# Зайти в MySQL-консоль
docker compose -f docker-compose.prod.yml exec mysql mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"

# Перезапустить все сервисы
docker compose -f docker-compose.prod.yml restart
```

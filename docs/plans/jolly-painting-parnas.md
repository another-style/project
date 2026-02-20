# План: Rate limiting для комментариев и голосов

## Контекст

Маршруты `comments.store` и `comments.vote` полностью открыты — нет rate limiting. Атакуют множественными POST-запросами. Redis уже настроен как cache store. Используем встроенный `throttle` middleware Laravel.

## Шаги

### 1. Зарегистрировать rate limiter'ы в `app/Providers/AppServiceProvider.php`

В метод `boot()` добавить:
- `RateLimiter::for('comment-store', ...)` — 5 запросов в минуту по IP
- `RateLimiter::for('comment-vote', ...)` — 30 запросов в минуту по IP

### 2. Добавить middleware в `routes/web.php`

- `comments.store` → `throttle:comment-store`
- `comments.vote` → `throttle:comment-vote`

## Файлы

| Файл | Действие |
|---|---|
| `app/Providers/AppServiceProvider.php` | Изменить |
| `routes/web.php` | Изменить |

## Проверка

1. `docker compose exec php composer test`

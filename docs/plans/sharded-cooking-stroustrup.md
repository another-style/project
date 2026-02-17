# План: Добавить Policy для авторизации действий в Filament

## Контекст

Permissions (`User.view`, `User.create` и т.д.) уже созданы в миграции, но Policy-классы отсутствуют. Filament 3 автоматически использует Laravel Policy для авторизации — если Policy не зарегистрирована, доступ разрешён всем.

## Что нужно сделать

### 1. Создать 3 Policy-класса

- `app/Policies/UserPolicy.php` — для модели `App\Models\User`
- `app/Policies/RolePolicy.php` — для модели `Spatie\Permission\Models\Role`
- `app/Policies/PermissionPolicy.php` — для модели `Spatie\Permission\Models\Permission`

Каждый Policy содержит методы, которые Filament вызывает автоматически:

| Метод Policy   | Действие Filament       | Permission     |
|----------------|-------------------------|----------------|
| `viewAny`      | Список / навигация      | `Entity.view`  |
| `view`         | Просмотр записи         | `Entity.view`  |
| `create`       | Создание записи         | `Entity.create`|
| `update`       | Редактирование записи   | `Entity.update`|
| `delete`       | Удаление записи         | `Entity.delete`|
| `deleteAny`    | Массовое удаление       | `Entity.delete`|

### 2. Зарегистрировать Policy в `AppServiceProvider`

Laravel автоматически обнаруживает Policy для моделей из `App\Models`, но для моделей spatie (`Spatie\Permission\Models\Role`, `Spatie\Permission\Models\Permission`) нужна явная регистрация через `Gate::policy()` в `AppServiceProvider::boot()`.

## Файлы

- **Новые**: `app/Policies/UserPolicy.php`, `app/Policies/RolePolicy.php`, `app/Policies/PermissionPolicy.php`
- **Изменяемые**: `app/Providers/AppServiceProvider.php` (регистрация Policy для spatie-моделей)

## Проверка

1. Войти под пользователем с ограниченной ролью (без `User.create`)
2. Убедиться, что кнопка "Создать пользователя" не отображается
3. Убедиться, что прямой доступ к `/admin/users/create` возвращает 403

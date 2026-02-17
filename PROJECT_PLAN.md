# Project Plan

## Stack
- Laravel 12 — backend
- Inertia.js + Vue — frontend (public-facing)
- Filament — admin panel (route /admin)
- spatie/laravel-permission — roles & permissions

## Docker Environment
- PHP 8.2+ (FPM)
- Nginx
- MySQL 8
- Redis
- Node.js (Vite, frontend build)
- docker-compose for orchestration

## Setup Steps
1. Create docker-compose.yml and Dockerfiles (PHP-FPM, Nginx, Node)
2. Create Laravel 12 project inside PHP container
3. Install Laravel Breeze with Vue + Inertia starter
4. Install Filament admin panel
5. Install spatie/laravel-permission
6. Configure .env for Docker (DB, Redis, app URL)
7. Run migrations

## Notes
- Filament (Livewire + Alpine.js) and Inertia + Vue coexist without conflicts
- User is new to frontend — Vue chosen for lower learning curve
- Project may be moved from Windows (D:\dev\project) to WSL for better Docker performance

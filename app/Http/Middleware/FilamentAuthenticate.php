<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

class FilamentAuthenticate extends BaseAuthenticate
{
    protected function authenticate($request, array $guards): void
    {
        $auth = $this->auth->guard(Filament::getAuthGuard());

        if (! $auth->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        $user = $auth->user();

        if ($user && method_exists($user, 'canAccessPanel') && ! $user->canAccessPanel(Filament::getCurrentPanel())) {
            $auth->logout();

            $this->unauthenticated($request, $guards);
        }
    }
}

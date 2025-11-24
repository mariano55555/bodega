<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogAuthenticationActivity
{
    /**
     * Handle Login events.
     */
    public function handleLogin(Login $event): void
    {
        // Check if this is a "remember me" re-authentication (session restore)
        // vs a manual login. Remember me logins happen via cookie, not form submission.
        $isRememberMeLogin = $event->remember || !request()->hasSession() || !request()->session()->has('_token');

        // Only log manual logins, not automatic session restores
        if (request()->isMethod('POST') || !$isRememberMeLogin) {
            activity()
                ->causedBy($event->user)
                ->withProperties([
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'via_remember' => $event->remember,
                ])
                ->log('Inicio de sesión exitoso');
        }
    }

    /**
     * Handle Logout events.
     */
    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            activity()
                ->causedBy($event->user)
                ->withProperties([
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('Cierre de sesión');
        }
    }

    /**
     * Handle Failed login events.
     */
    public function handleFailed(Failed $event): void
    {
        activity()
            ->withProperties([
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Intento de inicio de sesión fallido');
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe($events): array
    {
        return [
            Login::class => 'handleLogin',
            Logout::class => 'handleLogout',
            Failed::class => 'handleFailed',
        ];
    }
}

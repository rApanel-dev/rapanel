<?php

namespace App\Listeners;

use App\Models\ActionLog;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        ActionLog::create([
            // Si el email corresponde a una cuenta existente (contraseña
            // incorrecta), registramos su id; si el email no existe, queda null.
            'user_id'    => $event->user?->getAuthIdentifier(),
            'category'   => 'AUTH',
            'action'     => 'login_failed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata'   => [
                'email' => $event->credentials['email'] ?? null,
                'guard' => $event->guard,
            ],
        ]);
    }
}

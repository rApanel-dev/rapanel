<?php

namespace App\Listeners;

use App\Models\ActionLog;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        ActionLog::create([
            'user_id'    => null,
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

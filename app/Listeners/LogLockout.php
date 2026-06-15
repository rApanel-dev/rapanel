<?php

namespace App\Listeners;

use App\Models\ActionLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LogLockout
{
    public function handle(Lockout $event): void
    {
        $email = $event->request->input('email', '');
        $key   = Str::transliterate(Str::lower($email) . '|' . $event->request->ip());

        ActionLog::create([
            'user_id'    => null,
            'category'   => 'AUTH',
            'action'     => 'login_lockout',
            'ip_address' => $event->request->ip(),
            'user_agent' => $event->request->userAgent(),
            'metadata'   => [
                'email'               => $email,
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ],
        ]);
    }
}

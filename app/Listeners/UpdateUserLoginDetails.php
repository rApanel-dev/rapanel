<?php

namespace App\Listeners;

use App\Models\ActionLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;

class UpdateUserLoginDetails
{
    public function handle(Login $event): void
    {
        $event->user->update([
            'last_login' => Carbon::now(),
            'last_ip'    => request()->ip(),
        ]);

        ActionLog::create([
            'user_id'    => $event->user->id,
            'category'   => 'AUTH',
            'action'     => 'login_success',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata'   => ['guard' => $event->guard],
        ]);
    }
}

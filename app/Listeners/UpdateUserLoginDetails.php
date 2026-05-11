<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Carbon;

class UpdateUserLoginDetails
{
    public function handle(Login $event): void
    {
        // $event->user es el usuario maestro que acaba de loguearse
        $event->user->update([
            'last_login' => Carbon::now(),
            'last_ip'    => request()->ip(), // Captura la IP automáticamente
        ]);
    }
}

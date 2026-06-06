<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $expireMinutes = Config::get('auth.passwords.users.expire', 60);
        $serverName    = config('services.ra.server_name', 'rApanel');

        return (new MailMessage)->view(
            'emails.password-reset',
            [
                'url'           => $url,
                'serverName'    => $serverName,
                'expireMinutes' => $expireMinutes,
                'locale'        => Lang::getLocale(),
                'subject'       => Lang::get('Reset your password') . ' — ' . $serverName,
            ]
        )->subject(Lang::get('Reset your password') . ' — ' . $serverName);
    }
}

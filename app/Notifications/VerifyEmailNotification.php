<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        $expireMinutes = Config::get('auth.verification.expire', 60);
        $serverName    = config('services.ra.server_name', 'rApanel');

        return (new MailMessage)->view(
            'emails.verify-email',
            [
                'url'           => $url,
                'serverName'    => $serverName,
                'expireMinutes' => $expireMinutes,
                'locale'        => Lang::getLocale(),
                'subject'       => Lang::get('Verify your email address') . ' — ' . $serverName,
            ]
        )->subject(Lang::get('Verify your email address') . ' — ' . $serverName);
    }

    protected function verificationUrl($notifiable): string
    {
        $expireMinutes = Config::get('auth.verification.expire', 60);

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes($expireMinutes),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}

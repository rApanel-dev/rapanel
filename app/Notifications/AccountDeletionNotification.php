<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class AccountDeletionNotification extends Notification
{
    public function __construct(
        private readonly string $url,
        private readonly string $datetime,
        private readonly string $ip,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $serverName = config('services.ra.server_name', 'rApanel');

        return (new MailMessage)->view(
            'emails.account-deletion',
            [
                'url'        => $this->url,
                'serverName' => $serverName,
                'datetime'   => $this->datetime,
                'ip'         => $this->ip,
                'locale'     => Lang::getLocale(),
                'subject'    => Lang::get('account_deletion_subject') . ' — ' . $serverName,
            ]
        )->subject(Lang::get('account_deletion_subject') . ' — ' . $serverName);
    }
}

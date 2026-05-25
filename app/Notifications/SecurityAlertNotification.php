<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class SecurityAlertNotification extends Notification
{
    public function __construct(
        private readonly string  $eventKey,   // 'password_changed' | 'email_changed'
        private readonly string  $ip,
        private readonly string  $datetime,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $serverName = config('services.ra.server_name', 'rApanel');

        $subject = Lang::get('security_alert_subject') . ' — ' . $serverName;

        return (new MailMessage)->view(
            'emails.security-alert',
            [
                'serverName' => $serverName,
                'eventKey'   => $this->eventKey,
                'ip'         => $this->ip,
                'datetime'   => $this->datetime,
                'resetUrl'   => route('password.request'),
                'locale'     => Lang::getLocale(),
                'subject'    => $subject,
            ]
        )->subject($subject);
    }
}

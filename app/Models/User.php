<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'last_ip',
    'last_login',
    'country',
    'locale',
    'birthdate',
    'password',
    'donation_points',
    'vote_points',
    'role',
    'api_token'
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'api_token'
])]
// Añadimos 'implements MustVerifyEmail'
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valores predeterminados para los atributos del modelo.
     */
    protected $attributes = [
        'country' => 'CL',
        'role' => 'User',
        'status' => 1,
        'donation_points' => 0,
        'vote_points' => 0,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'last_login'               => 'datetime',
            'two_factor_confirmed_at'  => 'datetime',
            'password'                 => 'hashed',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorRecoveryCodes(): array
    {
        if (! $this->two_factor_recovery_codes) {
            return [];
        }
        return json_decode(decrypt($this->two_factor_recovery_codes), true) ?? [];
    }

    /**
     * MAGIA DEL .ENV: Comprueba si el usuario tiene su correo verificado.
     */
    public function sendEmailVerificationNotification(): void
    {
        $notification = new VerifyEmailNotification;

        if ($this->locale) {
            $notification = $notification->locale($this->locale);
        }

        $this->notify($notification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $notification = new ResetPasswordNotification($token);

        if ($this->locale) {
            $notification = $notification->locale($this->locale);
        }

        $this->notify($notification);
    }

    public function hasVerifiedEmail(): bool
    {
        if (! config('services.ra.require_email_verify', false)) {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }
    /**
     * Obtener las cuentas de juego (rAthena) vinculadas a esta cuenta maestra.
     */
    public function gameAccounts()
    {
        return $this->hasMany(GameAccount::class, 'master_id', 'id');
    }
}

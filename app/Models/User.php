<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; // <-- Descomentado
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
    'password', 
    'donation_points', 
    'vote_points', 
    'role', 
    'status', 
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * MAGIA DEL .ENV: Comprueba si el usuario tiene su correo verificado.
     */
    public function hasVerifiedEmail()
    {
        // Si el panel NO requiere verificación desde el .env, le decimos a Laravel que "ya está verificado"
        if (env('RA_REQUIRE_EMAIL_VERIFY', false) === false) {
            return true;
        }

        // Si es true, hace el comportamiento normal de Laravel (revisa si la fecha no es nula)
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

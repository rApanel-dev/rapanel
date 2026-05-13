<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    // Para que Laravel trate expires_at como un objeto de fecha automáticamente
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Obtener el usuario dueño de este token de enlace.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

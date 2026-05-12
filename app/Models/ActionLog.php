<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionLog extends Model
{
    // SIN el prefijo ra_, Laravel lo inyecta automáticamente gracias a tu conexión
    protected $connection = 'mysql';
    protected $table = 'action_logs'; 

    protected $fillable = [
        'user_id',
        'category',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    // Convertimos la columna metadata (JSON) a un array de PHP
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // Relación con el usuario web
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

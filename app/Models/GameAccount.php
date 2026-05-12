<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameAccount extends Model
{
    use HasFactory;

    // 1. Especificamos la conexión a usar (sin prefijos)
    protected $connection = 'mysql_main';

    // 2. Especificamos la tabla exacta en el emulador
    protected $table = 'login';

    // 3. rAthena usa 'account_id' como llave primaria, no 'id'
    protected $primaryKey = 'account_id';

    // 4. rAthena no usa created_at / updated_at por defecto
    public $timestamps = false;

    // 5. Campos permitidos para inserción masiva
    protected $fillable = [
        'master_id', // Nuestro campo mágico para vincular a ra_user
        'created_at',
        'userid',
        'user_pass',
        'sex',
        'email',
        'group_id',
        'state',
        'unban_time',
        'expiration_time',
        'logincount',
        'lastlogin',
        'last_ip',
        'birthdate',
        'character_slots',
        'pincode',
        'pincode_change',
        'vip_time',
        'old_group',
        'web_auth_token',
        'web_auth_token_enabled',
    ];

    // Relación Inversa: Esta cuenta de juego pertenece a una Cuenta Maestra (ra_user)
    public function masterAccount()
    {
        return $this->belongsTo(User::class, 'master_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $connection = 'mysql_main';
    protected $table = 'char';
    protected $primaryKey = 'char_id';
    public $timestamps = false;

    protected $fillable = [
        'last_map', 'last_x', 'last_y',
        'save_map', 'save_x', 'save_y',
        'hair', 'hair_color', 'clothes_color', 'body',
    ];

    public function gameAccount()
    {
        return $this->belongsTo(GameAccount::class, 'account_id', 'account_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MvpCard extends Model
{
    protected $table = 'mvp_cards';

    protected $fillable = [
        'item_id',
        'mob_id',
        'name_override',
        'is_active',
    ];

    protected $casts = [
        'item_id'   => 'integer',
        'mob_id'    => 'integer',
        'is_active' => 'boolean',
    ];
}

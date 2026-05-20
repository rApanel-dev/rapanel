<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MvpCard extends Model
{
    protected $table = 'mvp_cards';

    protected $fillable = [
        'item_id',
        'name_override',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'item_id'   => 'integer',
        'is_active' => 'boolean',
    ];
}

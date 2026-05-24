<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpawnEntry extends Model
{
    protected $fillable = [
        'map_name', 'mob_id', 'mob_name',
        'x', 'y', 'rx', 'ry',
        'amount', 'delay1', 'delay2',
    ];

    protected $casts = [
        'mob_id' => 'integer',
        'x'      => 'integer',
        'y'      => 'integer',
        'rx'     => 'integer',
        'ry'     => 'integer',
        'amount' => 'integer',
        'delay1' => 'integer',
        'delay2' => 'integer',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobDb extends Model
{
    protected $table      = 'mob_db';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    protected $keyType    = 'integer';

    protected $fillable = [
        'id', 'aegis_name', 'name', 'level', 'hp', 'base_exp', 'mvp_exp',
        'is_mvp', 'element', 'race', 'size', 'class',
        'stats', 'modes', 'race_groups', 'drops', 'mvp_drops', 'skills',
    ];

    protected $casts = [
        'id'         => 'integer',
        'level'      => 'integer',
        'hp'         => 'integer',
        'base_exp'   => 'integer',
        'mvp_exp'    => 'integer',
        'is_mvp'     => 'boolean',
        'stats'      => 'array',
        'modes'      => 'array',
        'race_groups'=> 'array',
        'drops'      => 'array',
        'mvp_drops'  => 'array',
        'skills'     => 'array',
    ];
}

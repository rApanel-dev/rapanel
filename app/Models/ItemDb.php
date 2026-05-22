<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDb extends Model
{
    protected $table      = 'item_db';
    protected $primaryKey = 'item_id';
    public    $incrementing = false;
    protected $keyType    = 'integer';

    protected $fillable = [
        'item_id',
        'aegis_name',
        'name',
        'display_name',
        'type',
        'subtype',
        'slots',
        'description_html',
        'is_custom',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_custom'  => 'boolean',
        'slots'      => 'integer',
        'item_id'    => 'integer',
    ];
}

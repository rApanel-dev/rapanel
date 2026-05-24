<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapSize extends Model
{
    protected $primaryKey = 'map_name';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['map_name', 'width', 'height'];

    protected $casts = [
        'width'  => 'integer',
        'height' => 'integer',
    ];

    public function spawns()
    {
        return $this->hasMany(SpawnEntry::class, 'map_name', 'map_name');
    }
}

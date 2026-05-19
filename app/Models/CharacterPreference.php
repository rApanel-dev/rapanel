<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterPreference extends Model
{
    protected $connection = 'mysql';
    protected $table = 'character_preferences';

    protected $fillable = ['char_id', 'hide_from_rankings'];

    protected $casts = ['hide_from_rankings' => 'boolean'];
}

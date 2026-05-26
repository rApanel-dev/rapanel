<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DropRate extends Model
{
    protected $connection  = 'mysql';
    protected $table       = 'drop_rates';
    protected $primaryKey  = 'key';
    public    $incrementing = false;
    protected $keyType     = 'string';
    public    $timestamps  = false;

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'integer'];
}

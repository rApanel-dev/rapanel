<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    protected $connection = 'mysql';
    protected $table = 'user_logs';

    protected $fillable = [
        'user_id',
        'field',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

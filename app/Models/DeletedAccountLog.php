<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedAccountLog extends Model
{
    protected $connection = 'mysql';
    protected $table      = 'deleted_accounts_logs';

    protected $fillable = [
        'account_id',
        'original_userid',
        'web_user_id',
    ];

    public function webUser()
    {
        return $this->belongsTo(User::class, 'web_user_id');
    }
}

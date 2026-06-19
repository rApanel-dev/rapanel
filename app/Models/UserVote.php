<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserVote extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'user_id', 'vote_site_id', 'voted_at', 'points_awarded', 'ip_address',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voteSite()
    {
        return $this->belongsTo(VoteSite::class);
    }

    public static function lastVote(int $userId, int $siteId): ?self
    {
        return static::where('user_id', $userId)
            ->where('vote_site_id', $siteId)
            ->latest('voted_at')
            ->first();
    }
}

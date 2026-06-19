<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteSite extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'name', 'icon_url', 'vote_url', 'callback_type',
        'callback_secret', 'callback_ip', 'points_per_vote',
        'cooldown_hours', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'points_per_vote' => 'integer',
        'cooldown_hours'  => 'integer',
        'sort_order'      => 'integer',
    ];

    public function userVotes()
    {
        return $this->hasMany(UserVote::class);
    }

    public function buildVoteUrl(int $userId): string
    {
        return str_replace('{user_id}', $userId, $this->vote_url);
    }
}

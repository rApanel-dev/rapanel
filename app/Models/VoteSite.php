<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoteSite extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'name', 'icon_url', 'vote_url', 'callback_type',
        'callback_secret', 'callback_ip', 'points_per_vote',
        'cooldown_hours', 'is_active', 'sort_order', 'border_color',
    ];

    public static function colorHex(string $color): string
    {
        return match ($color) {
            'gold'    => '#F1C40F',
            'success' => '#2ECC71',
            'purple'  => '#a855f7',
            'danger'  => '#E74C3C',
            'navy'    => '#334155',
            default   => '#4A90E2', // blue
        };
    }

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

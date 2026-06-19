<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'user_id', 'package_id', 'platform', 'transaction_id',
        'amount_usd', 'cashpoints_awarded', 'status', 'metadata',
        'notes', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'amount_usd'        => 'decimal:2',
        'cashpoints_awarded'=> 'integer',
        'metadata'          => 'array',
        'approved_at'       => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(DonationPackage::class, 'package_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markCompleted(int $cashpoints, ?User $admin = null): void
    {
        $this->update([
            'status'             => 'completed',
            'cashpoints_awarded' => $cashpoints,
            'approved_by'        => $admin?->id,
            'approved_at'        => now(),
        ]);

        if ($this->user_id) {
            User::where('id', $this->user_id)->increment('donation_points', $cashpoints);
        }
    }
}

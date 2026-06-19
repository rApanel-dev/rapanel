<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DonationPackage extends Model
{
    protected $connection = 'mysql';

    protected $fillable = [
        'title', 'description', 'image_path', 'price_usd',
        'cashpoints', 'bonus_percent', 'is_active', 'sort_order',
        'border_color', 'is_featured',
    ];

    protected $casts = [
        'price_usd'    => 'decimal:2',
        'cashpoints'   => 'integer',
        'bonus_percent'=> 'integer',
        'is_active'    => 'boolean',
        'sort_order'   => 'integer',
        'is_featured'  => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function totalCashpoints(): int
    {
        return $this->cashpoints + (int) round($this->cashpoints * $this->bonus_percent / 100);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function donations()
    {
        return $this->hasMany(Donation::class, 'package_id');
    }
}

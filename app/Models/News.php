<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'body',
        'featured_image',
        'type',
        'is_published',
        'is_pinned',
        'allow_comments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published'  => 'boolean',
        'is_pinned'     => 'boolean',
        'allow_comments' => 'boolean',
        'type'          => 'integer',
    ];

    public static function typeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Announcement',
            2 => 'Event',
            default => 'General',
        };
    }

    public function getExcerptAttribute(): string
    {
        return Str::limit(strip_tags($this->body ?? ''), 120);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}

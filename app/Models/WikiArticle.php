<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WikiArticle extends Model
{
    protected $fillable = [
        'section_id', 'title', 'slug', 'content', 'featured_image',
        'focal_x', 'focal_y', 'show_toc', 'sort_order', 'is_published', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_toc'     => 'boolean',
        'sort_order'   => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(WikiSection::class, 'section_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getExcerptAttribute(): string
    {
        $text = strip_tags($this->content ?? '');
        return mb_strlen($text) > 180 ? mb_substr($text, 0, 180) . '…' : $text;
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content ?? ''));
        return max(1, (int) ceil($wordCount / 200));
    }
}

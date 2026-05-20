<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'image_path',
        'is_external', 'is_external_url_hidden', 'file_url', 'file_name', 'file_path',
        'is_only_auth', 'is_active', 'download_count', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_external'            => 'boolean',
        'is_external_url_hidden' => 'boolean',
        'is_only_auth'           => 'boolean',
        'is_active'              => 'boolean',
        'download_count'         => 'integer',
        'sort_order'             => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(DownloadCategory::class, 'category_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'content',
        'image_path',
        'news_title',
        'news_content',
        'news_image_path',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getTitleNormalizedAttribute(): string
    {
        return (string) ($this->title ?: $this->news_title ?: '');
    }

    public function getContentNormalizedAttribute(): string
    {
        return (string) ($this->content ?: $this->news_content ?: '');
    }

    public function getImageNormalizedAttribute(): ?string
    {
        $path = (string) ($this->image_path ?: $this->news_image_path ?: '');
        return $path !== '' ? $path : null;
    }

    public function getExcerptAttribute(): string
    {
        $text = strip_tags($this->content_normalized);
        return Str::limit(trim($text), 160);
    }
}

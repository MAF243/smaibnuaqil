<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    use SoftDeletes;

    protected $table = 'news_posts';
    protected $fillable = ['legacy_news_id','category_id','slug','title','excerpt','content','featured_image_id','status','published_at','published_by_account_id','created_by_account_id','updated_by_account_id'];
    protected $casts = ['published_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function featuredImage() { return $this->belongsTo(MediaAsset::class, 'featured_image_id'); }
    public function category() { return $this->belongsTo(NewsCategory::class, 'category_id'); }

    public function getImagePathAttribute(): ?string
    {
        return $this->featuredImage?->file_path;
    }

    public function getTitleNormalizedAttribute(): string
    {
        return (string) ($this->title ?: '');
    }

    public function getContentNormalizedAttribute(): string
    {
        return (string) ($this->content ?: '');
    }

    public function getImageNormalizedAttribute(): ?string
    {
        return $this->image_path ?: null;
    }


    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    public function getExcerptAttribute($value): string
    {
        if (is_string($value) && trim($value) !== '') {
            return $value;
        }
        return Str::limit(trim(strip_tags($this->content_normalized)), 160);
    }
}

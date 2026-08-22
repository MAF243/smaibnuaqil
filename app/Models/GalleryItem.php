<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryItem extends Model
{
    use SoftDeletes;

    protected $table = 'gallery_items';

    protected $fillable = [
        'legacy_gallery_id',
        'title',
        'description',
        'image_asset_id',
        'is_published',
        'sort_order',
        'published_at',
        'created_by_account_id',
        'updated_by_account_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['image_path', 'uploaded_at'];

    public function imageAsset()
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    public function getImagePathAttribute(): ?string
    {
        return $this->imageAsset?->file_path;
    }

    public function getUploadedAtAttribute()
    {
        return $this->published_at ?: $this->created_at;
    }
}

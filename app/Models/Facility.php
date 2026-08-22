<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $table = 'facilities';

    protected $fillable = [
        'name',
        'short_description',
        'content',
        'slug',
        'icon_class',
        'facility_key',
        'modal_title',
        'modal_description',
        'modal_image_path',
        'image_asset_id',
        'is_published',
        'display_order',
        'published_at',
        'created_by_account_id',
        'updated_by_account_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'display_order' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    protected $appends = ['image_path'];

    public function imageAsset()
    {
        return $this->belongsTo(MediaAsset::class, 'image_asset_id');
    }

    public function getImagePathAttribute(): ?string
    {
        return $this->imageAsset?->file_path ?: $this->modal_image_path;
    }
}

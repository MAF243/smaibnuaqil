<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyGalleryItem extends Model
{
    protected $table = 'gallery';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'is_published',
        'uploaded_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'uploaded_at' => 'datetime',
    ];
}

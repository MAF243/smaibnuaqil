<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $table = 'media_assets';
    protected $fillable = ['disk','file_path','collection','original_name','mime_type','file_size','alt_text','is_public','uploaded_by_account_id'];

    protected $casts = [
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

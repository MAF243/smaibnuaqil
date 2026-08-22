<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'page_key', 'slug', 'title', 'excerpt', 'content', 'content_json', 'status', 'published_at', 'is_published', 'created_by_account_id', 'updated_by_account_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

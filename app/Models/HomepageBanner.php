<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageBanner extends Model
{
    protected $table = 'homepage_banners';
    protected $fillable = ['title','subtitle','cta_label','cta_url','image_asset_id','is_active','starts_at','ends_at'];
    protected $casts = ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}

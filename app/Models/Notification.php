<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = ['account_id','category','type','title','message','url','is_read','read_at'];
    protected $casts = ['is_read' => 'boolean', 'read_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function getLinkAttribute(): ?string
    {
        return $this->url;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    public $timestamps = false;
    protected $fillable = ['actor_account_id','module','action','target_type','target_id','summary','before_json','after_json','ip_address','user_agent','created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function getDescriptionAttribute(): ?string
    {
        return $this->summary;
    }
}

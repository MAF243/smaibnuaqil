<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardNotification extends Model
{
    protected $table = 'dashboard_notifications';
    public $timestamps = false;

    protected $fillable = [
        'recipient_type', 'recipient_id', 'type', 'title', 'message', 'link', 'is_read', 'created_at', 'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'read_at' => 'datetime',
    ];
}

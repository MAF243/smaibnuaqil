<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    protected $table = 'admin_activity_logs';
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'action', 'subject_type', 'subject_id', 'description', 'properties', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}

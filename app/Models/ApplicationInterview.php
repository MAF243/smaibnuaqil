<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationInterview extends Model
{
    protected $table = 'application_interviews';
    protected $fillable = ['application_id','interview_type','scheduled_at','location','meeting_link','attendance_status','notes','created_by_account_id'];
    protected $casts = ['scheduled_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}

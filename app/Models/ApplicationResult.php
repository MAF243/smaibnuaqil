<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationResult extends Model
{
    protected $table = 'application_results';
    protected $fillable = ['application_id','result_status','announcement_title','announcement_body','document_path','published_at','published_by_account_id'];
    protected $casts = ['published_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}

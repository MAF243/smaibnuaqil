<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    protected $table = 'application_documents';
    protected $fillable = ['application_id','document_type','legacy_source','legacy_source_id','original_name','stored_name','file_path','mime_type','file_size','uploaded_by_account_id','verified_at','verified_by_account_id','verification_status','verification_notes'];
    protected $casts = ['verified_at' => 'datetime', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}

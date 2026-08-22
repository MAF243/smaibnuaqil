<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationGuardian extends Model
{
    protected $table = 'application_guardians';
    protected $fillable = ['application_id','guardian_type','full_name','phone','email','occupation','income_range','birth_place','birth_date','religion','relationship_to_student'];
    protected $casts = ['birth_date' => 'date', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}

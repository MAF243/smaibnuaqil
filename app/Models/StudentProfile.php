<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $table = 'student_profiles';
    protected $fillable = ['application_id','full_name','gender','birth_place','birth_date','religion','phone','address','hobby','goal','motivation'];
    protected $casts = ['birth_date' => 'date', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationStatusHistory extends Model
{
    protected $table = 'application_status_histories';
    public $timestamps = false;

    protected $fillable = [
        'application_id','student_id', 'from_status', 'to_status', 'actor_type', 'actor_id', 'changed_by_account_id', 'note', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function application()
    {
        return $this->belongsTo(StudentApplication::class, 'application_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyUser extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
    ];

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }


    public function account()
    {
        return $this->hasOne(Account::class, 'legacy_user_id', 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    protected $table = 'accounts';

    protected $fillable = [
        'account_type','legacy_user_id','legacy_admin_id','username','email','phone','password_hash','is_active','last_login_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'account_roles', 'account_id', 'role_id')->withTimestamps();
    }

    public function applications()
    {
        return $this->hasMany(StudentApplication::class, 'account_id');
    }
}

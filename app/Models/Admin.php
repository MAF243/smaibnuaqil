<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'role',
    ];


    public function account()
    {
        return $this->hasOne(Account::class, 'legacy_admin_id', 'id');
    }
}

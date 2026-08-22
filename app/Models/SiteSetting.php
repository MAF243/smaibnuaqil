<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'site_settings';
    protected $primaryKey = 'setting_name';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'setting_name',
        'setting_value',
    ];
}

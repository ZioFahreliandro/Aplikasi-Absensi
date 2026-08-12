<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'office_name',
        'office_lat',
        'office_lng',
        'office_radius',
        'office_checkin_time',
        'office_checkout_time',
        'office_ip',
        'enable_gps',
        'enable_ip'
    ];

    protected $casts = [
        'enable_gps' => 'boolean',
        'enable_ip' => 'boolean',
        'office_lat' => 'double',
        'office_lng' => 'double',
        'office_radius' => 'integer',
    ];
}

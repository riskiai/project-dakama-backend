<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalHour extends Model
{
    protected $fillable = [
        'ontime_start',
        'ontime_end',
        'late_time',
        'offtime',
        'duration',
        'bonus',
    ];

    public $timestamps = false;
}

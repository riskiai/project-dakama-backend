<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationalHour extends Model
{
    use SoftDeletes;

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

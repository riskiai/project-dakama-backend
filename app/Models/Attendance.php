<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    const ATTENDANCE_IMAGE_IN = 'attendances/in';
    const ATTENDANCE_IMAGE_OUT = 'attendances/out';

    const ATTENDANCE_IN = 'in';
    const ATTENDANCE_OUT = 'out';
    const ATTENDANCE_ABSENT = 'absent';

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'duration',
        'start_time',
        'end_time',
        'location_in',
        'location_lat_in',
        'location_long_in',
        'location_out',
        'location_lat_out',
        'location_long_out',
        'status',
        'image_in',
        'image_out',
        'is_late',
        'daily_salary',
        'hourly_salary',
        'hourly_overtime_salary',
        'transport',
        'makan',
        'bonus_ontime',
        'late_cut',
        'late_minutes',
        'is_settled',
        'type',
        'overtime_id'
    ];

    protected $casts = [
        "start_time" => "datetime",
        "end_time" => "datetime",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function overtime()
    {
        return $this->belongsTo(Overtime::class);
    }
}

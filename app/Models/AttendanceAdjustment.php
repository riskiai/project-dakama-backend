<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceAdjustment extends Model
{
    use SoftDeletes;

    const STATUS_WAITING = 'waiting';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'pic_id',
        'attendance_id',
        'old_start_time',
        'old_end_time',
        'new_start_time',
        'new_end_time',
        'reason',
        'status',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class);
    }

    public function notification()
    {
        return $this->morphOne(Notification::class, 'notifiable');
    }
}

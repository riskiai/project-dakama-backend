<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Overtime extends Model
{
    use SoftDeletes;

    const STATUS_WAITING = 'waiting';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'project_id',
        'budget_id',
        'duration',
        'request_date',
        'reason',
        'status',
        'salary_overtime',
        'pic_id',
        "reason_approval",
        'start_time',
        'end_time',
        'is_present',
        'makan'
    ];

    protected $casts = [
        'request_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function notification()
    {
        return $this->morphOne(Notification::class, 'notifiable');
    }

    public function attendance()
    {
        return $this->hasOne(Attendance::class);
    }
}

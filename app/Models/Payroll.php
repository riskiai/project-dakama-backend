<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    const STATUS_WAITING = 'waiting';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        "user_id",
        "pic_id", // from user created
        "total_attendance",
        "total_daily_salary",
        "total_overtime",
        "total_late_cut",
        "total_loan", // from inputan
        "datetime", // from input (format: Y-m-d, Y-m-d)
        "notes", // opsional
        "status",
        "approved_at",
        "approved_by",
        "reason_approval",
    ];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approved()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function mutations()
    {
        return $this->morphMany(MutationLoan::class, 'mutable');
    }

    public function notification()
    {
        return $this->morphOne(Notification::class, 'notifiable');
    }
}

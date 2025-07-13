<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    const STATUS_WAITING = 'waiting';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'pic_id',
        'request_date',
        'nominal',
        'latest',
        'reason',
        'status',
        'is_settled',
        "reason_approval",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class);
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

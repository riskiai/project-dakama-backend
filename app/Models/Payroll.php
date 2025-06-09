<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
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
    ];

    public function pic() {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutationLoan extends Model
{
    const TYPE_APPROVAL = 1;
    const TYPE_PAYMENT = 2;

    protected $fillable = [
        'user_id',
        'mutable_id',
        'mutable_type',
        'increase',
        'decrease',
        'latest',
        'total',
        'created_by',
        'description',
        'payment_at',
        'payment_method',
        'type'
    ];

    public function mutable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

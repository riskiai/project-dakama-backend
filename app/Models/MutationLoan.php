<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutationLoan extends Model
{
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

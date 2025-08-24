<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'from_user_id',
        'notifiable_type',
        'notifiable_id',
        'title',
        'description',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    public function requestBy()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    protected $fillable = [
        'notification_id',
        'user_id',
        'role_id',
        'read_by',
        'read_at'
    ];

    public $timestamps = false;

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}

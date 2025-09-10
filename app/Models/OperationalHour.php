<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperationalHour extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ontime_start',
        'ontime_end',
        'late_time',
        'offtime',
        'duration',
        'bonus',
    ];

    public $timestamps = false;
    protected $hidden = ['duration', 'bonus'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'operational_hour_id', 'id');
    }
}

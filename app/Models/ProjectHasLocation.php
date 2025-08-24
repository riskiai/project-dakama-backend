<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectHasLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'longitude',
        'latitude',
        'radius',
        'name',
        'is_default',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectHasLocation extends Model
{
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

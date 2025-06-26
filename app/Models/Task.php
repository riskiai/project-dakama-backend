<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory, SoftDeletes; 

    const JASA = 1;
    const MATERIAL = 2;

    protected $table = 'tasks';

    protected $fillable = [
        'project_id',
        'nama_task',
        'type',
        'nominal',
    ];

     public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

      public function projectsPivot()
    {
        return $this->belongsToMany(
            Project::class,
            'projects_user_tasks',
            'tasks_id',     // foreign key di pivot yg merujuk ke tasks
            'project_id'    // foreign key di pivot yg merujuk ke projects
        );
    }
}

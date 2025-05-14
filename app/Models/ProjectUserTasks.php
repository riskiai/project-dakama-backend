<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectUserTasks extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects_user_tasks';

    // Tentukan kolom-kolom yang bisa diisi
    protected $fillable = [
        'project_id',   
        'user_id',     
        'tasks_id',   
        'created_at',   
        'updated_at',  
    ];

    // Tentukan relasi dengan Project (many-to-one)
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    // Tentukan relasi dengan User (many-to-one)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function tasks()
    {
        return $this->belongsTo(Task::class, 'tasks_id', 'id');
    }

}

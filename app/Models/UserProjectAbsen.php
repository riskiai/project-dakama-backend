<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserProjectAbsen extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'users_project_absen';

    const HADIR = 1;
    const BOLOS = 2;
    const SAKIT = 3;
    const CUTI = 4;
    const TERLAMBAT = 5;

    protected $fillable = [
        'user_id',
        'project_id',
        'location_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id')->withDefault();
    }

    public function location()
    {
        return $this->belongsTo(ProjectHasLocation::class, 'location_id', 'id')->withDefault();
    }
}

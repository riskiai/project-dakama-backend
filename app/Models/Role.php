<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    const OWNER = 1;
    const ADMIN = 2;
    const SUPERVISOR = 3;
    const KARYAWAN = 4;

    protected $table = 'roles';

    protected $fillable = [
        'role_name',
    ];
}

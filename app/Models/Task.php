<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory, SoftDeletes; 

    const JASA = 1;
    const MATERIAL = 2;

    protected $table = 'tasks';

    protected $fillable = [
        'nama_task',
        'type',
        'nominal',
    ];
}

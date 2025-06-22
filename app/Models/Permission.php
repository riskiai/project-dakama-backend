<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as ModelsPermission;

class Permission extends ModelsPermission
{
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id')->with('children')->select("id", "name", "parent_id");
    }

    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }
}

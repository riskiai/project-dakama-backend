<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectNameCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return $this->collection->transform(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
            ];
        })->toArray();
    }
}


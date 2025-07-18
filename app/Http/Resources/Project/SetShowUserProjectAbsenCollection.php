<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetShowUserProjectAbsenCollection extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user'       => [
                'id'   => $this->user_id,
                'name' => $this->user->name ?? null,
            ],
            'project'    => [
                'id'   => $this->project_id,
                'name' => $this->project->name ?? null,
            ],
            'location'   => $this->location,
        ];
    }
}

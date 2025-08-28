<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetShowUserProjectAbsenCollection extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'user' => [
                'id'   => $this->user_id,
                'name' => $this->user->name ?? null,

                'role' => [
                    'id'   => optional($this->user)->role_id,
                    'name' => optional(optional($this->user)->role)->role_name,
                ],

                'divisi' => [
                    'id'   => optional($this->user)->divisi_id,
                    'name' => optional(optional($this->user)->divisi)->name,
                ],
            ],

            'project' => [
                'id'   => $this->project_id,
                'name' => $this->project->name ?? null,
            ],

            'location' => $this->location,
        ];
    }
}

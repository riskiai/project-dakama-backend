<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SetUserProjectAbsenCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $item) {
            $data[] = [
                'id'         => $item->id,
                'user'       => [
                    'id'   => $item->user_id,
                    'name' => $item->user->name ?? null,
                ],
                'project'    => [
                    'id'   => $item->project_id,
                    'name' => $item->project->name ?? null,
                ],
                'location'   => $item->location,
            ];
        }

        return $data;
    }
}

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
                'longitude'  => $item->longitude ?? null,
                'latitude'   => $item->latitude ?? null,
                'radius'     => $item->radius ?? null,
                'status'     => $item->status ?? null,
                'jam_masuk'  => $item->jam_masuk ?? null,
                'jam_pulang' => $item->jam_pulang ?? null,
                'keterangan' => $item->keterangan ?? null,
            ];
        }

        return $data;
    }
}

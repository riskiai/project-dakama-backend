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

                      'role'    => [
                        'id'   => $item->user->role_id ?? null,
                        'name' => optional($item->user->role)->role_name, // sesuaikan kolom nama role
                    ],

                    // Tambah divisi
                    'divisi'  => [
                        'id'   => $item->user->divisi_id ?? null,
                        'name' => optional($item->user->divisi)->name,    // sesuaikan kolom nama divisi
                    ],
                    
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

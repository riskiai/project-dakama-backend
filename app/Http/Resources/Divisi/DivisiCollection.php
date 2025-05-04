<?php

namespace App\Http\Resources\Divisi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DivisiCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $divisi) {
            $data[] = [
                'id' => $divisi->id,
                'name' => $divisi->name,
                'kode_divisi' => $divisi->kode_divisi,
                'created_at' => $divisi->created_at,
                'updated_at' => $divisi->updated_at,
            ];
        }

        return $data;
    }
}

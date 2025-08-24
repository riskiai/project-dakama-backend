<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        return [
            "id" => $this->id,
            "name" => $this->name,
            "email" => $this->email,
            "nomor_karyawan" => $this->nomor_karyawan,
            "divisi_id" => $this->divisi_id,
            "email_verified_at" => $this->email_verified_at,
            "token" => $this->token,
            "status" => $this->status,
            "load" => $this->load,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "access" => $this->access,
        ];
    }
}

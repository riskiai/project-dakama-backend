<?php

namespace App\Http\Resources\Overtime;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeResource extends JsonResource
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
            'id' => $this->id,
            'user_id' => $this->user?->id,
            'user_name' => $this->user?->name,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'budget_id' => $this->budget_id,
            'budget_nama' => $this->budget->nama_budget,
            'request_date' => $this->request_date,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

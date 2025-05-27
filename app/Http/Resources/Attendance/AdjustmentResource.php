<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_by' => $this->user->name,
            'pic_name' => $this->pic->name,
            'old_start_time' => $this->old_start_time,
            'old_end_time' => $this->old_end_time,
            'new_start_time' => $this->new_start_time,
            'new_end_time' => $this->new_end_time,
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

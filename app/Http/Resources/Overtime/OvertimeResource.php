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
            'created_by' => $this->user?->name,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'task_id' => $this->task_id,
            'task_name' => $this->task->nama_task,
            'pic_id' => $this->pic_id,
            'pic_name' => $this->pic?->name,
            'duration' => $this->duration,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'request_date' => $this->request_date,
            'reason' => $this->reason,
            'reason_approval' => $this->reason_approval,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

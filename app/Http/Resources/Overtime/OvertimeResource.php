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
            'created_by' => $this->user->name,
            'project_id' => $this->project_id,
            'project_name' => $this->project->name,
            'task_id' => $this->task_id,
            'task_name' => $this->task->nama_task,
            'duration' => $this->duration,
            'request_date' => $this->request_date,
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

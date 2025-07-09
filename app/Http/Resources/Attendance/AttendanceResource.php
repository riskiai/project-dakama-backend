<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AttendanceResource extends JsonResource
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
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'location_in' => $this->location_in,
            'location_lat_in' => $this->location_lat_in,
            'location_long_in' => $this->location_long_in,
            'image_in' => Storage::url($this->image_in),
            'location_out' => $this->location_out,
            'location_lat_out' => $this->location_lat_out,
            'location_long_out' => $this->location_long_out,
            'image_out' => Storage::url($this->image_out),
            'status' => str()->of($this->status)->upper(),
            'present' => $this->isPresent($this),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    protected function isPresent($attendance)
    {
        if ($attendance->is_late) {
            return "LATE";
        }

        if ($attendance->bonus_ontime > 0) {
            return "ON TIME";
        }

        return "PRESENT";
    }
}

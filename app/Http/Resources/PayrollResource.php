<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "user_name" => $this->user->name,
            "pic_id" => $this->pic_id,
            "pic_name" => $this->pic->name,
            "approved_by" => $this->approved_by,
            "approved_name" => $this->approved?->name,
            "total_attendance" => $this->total_attendance,
            "total_daily_salary" => $this->total_daily_salary,
            "total_overtime" => $this->total_overtime,
            "total_late_cut" => $this->total_late_cut,
            "total_loan" => $this->total_loan,
            "datetime" => $this->datetime,
            "notes" => $this->notes,
            "status" => $this->status,
            "approved_at" => $this->approved_at,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at
        ];
    }
}

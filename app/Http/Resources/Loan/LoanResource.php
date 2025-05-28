<?php

namespace App\Http\Resources\Loan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
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
            "created_by" => $this->user->name,
            "approval_by" => $this->pic->name ?? "-",
            "request_date" => $this->request_date,
            "nominal" => $this->nominal,
            "latest" => $this->latest,
            "reason" => $this->reason,
            "status" => $this->status,
            "is_settled" => $this->is_settled,
            "approve_at" => $this->updated_at,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}

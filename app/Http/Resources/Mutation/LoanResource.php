<?php

namespace App\Http\Resources\Mutation;

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
            "request_by" => $this->user->name,
            "created_by" => $this->user->name,
            "increase" => $this->increase,
            "decrease" => $this->decrease,
            "latest" => $this->latest,
            "total" => $this->total,
            "description" => $this->description,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at
        ];
    }
}

<?php

namespace App\Http\Resources\Contact;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ContactAllCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return $this->collection->transform(function ($company) {
            return [
                "contact_type" => [
                    "id" => $company->contactType->id,
                    "name" => $company->contactType->name,
                ],
                'name' => $company->name,
            ];
        })->toArray();
    }
}

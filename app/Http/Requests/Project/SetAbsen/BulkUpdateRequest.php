<?php

namespace App\Http\Requests\Project\SetAbsen;

use App\Facades\MessageDakama;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

class BulkUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'users_detail'               => 'required|array|min:1',
            'users_detail.*.user_id'     => 'required|integer|exists:users,id',
            'users_detail.*.location_id' => 'nullable|integer|exists:project_has_locations,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse([
            'status'      => MessageDakama::WARNING,
            'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
            'message'     => $validator->errors()
        ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);

        throw new ValidationException($validator, $response);
    }
}

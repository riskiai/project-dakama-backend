<?php

namespace App\Http\Requests\Project\SetAbsen;

use App\Facades\MessageDakama;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

class CreateRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
              'project_id' => 'required|exists:projects,id',
              'longitude'  => 'nullable|string',
              'latitude'   => 'nullable|string',
              'radius'     => 'nullable|string',
              
              'user_id'    => 'required|array',
              'user_id.*'  => 'nullable|exists:users,id|numeric|min:1',
        ];
    }

     protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => is_array($this->input('user_id')) ? $this->input('user_id') : [$this->input('user_id')],
        ]);
    }


    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse([
            'status' => MessageDakama::WARNING,
            'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
            'message' => $validator->errors()
        ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);

        throw new ValidationException($validator, $response);
    }
}

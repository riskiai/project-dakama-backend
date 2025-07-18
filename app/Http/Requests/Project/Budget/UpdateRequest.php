<?php

namespace App\Http\Requests\Project\Budget;

use App\Facades\MessageDakama;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // diubah dari false agar dapat digunakan
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id'   => 'required|exists:projects,id',
            'nama_budget'  => 'required|string|max:255',
            'type'         => 'required|in:1,2', // 1: JASA, 2: MATERIAL
            'nominal'      => 'required|numeric|min:0',
            'unit'        => 'nullable|string|max:255', // kolom baru
            'stok'        => 'nullable|string|max:255', // kolom baru
        ];
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

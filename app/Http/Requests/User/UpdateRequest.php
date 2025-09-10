<?php

namespace App\Http\Requests\User;

use App\Facades\MessageDakama;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => 'nullable',
            'email' => 'nullable|email|unique:users,email,' . $userId,
            'role' => 'nullable|exists:roles,id',
            'divisi' => 'nullable|exists:divisis,id', // Tambahkan validasi untuk divisi
            'password' => 'nullable',
            'daily_salary' => 'required|numeric|min:0',
            'hourly_salary' => 'required|numeric|min:0',
            'hourly_overtime_salary' => 'required|numeric|min:0',
            'transport' => 'required|numeric|min:0',
            'makan'=> 'required|numeric|min:0',
            'nomor_karyawan' => 'nullable|string|min:1|max:255',
             'bank_name'      => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
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

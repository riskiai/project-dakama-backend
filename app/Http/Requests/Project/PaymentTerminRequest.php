<?php

namespace App\Http\Requests\Project;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PaymentTerminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'harga_termin_proyek' => 'nullable|numeric|min:0',
            'actual_payment'             => 'required|numeric|min:0|lte:harga_termin_proyek',
            'deskripsi_termin_proyek' => 'nullable|string',
            'type_termin_proyek' => 'nullable|in:1,2',
            'payment_date_termin_proyek' => 'nullable|date',
            // 'actual_payment'                        => 'nullable|numeric|min:0|max:100',
            'attachment_file_termin_proyek' => 'nullable|file|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072', 
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse([
            'status' => 'WARNING',
            'status_code' => 422,
            'message' => $validator->errors()
        ], 422);

        throw new ValidationException($validator, $response);
    }
}

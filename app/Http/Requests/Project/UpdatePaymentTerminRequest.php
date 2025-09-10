<?php

namespace App\Http\Requests\Project;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UpdatePaymentTerminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'riwayat_termin' => 'required|array',
            'riwayat_termin.*.id' => 'required|integer|exists:projects_termin,id',
            'riwayat_termin.*.harga_termin_proyek' => 'required|numeric|min:0',
            'riwayat_termin.*.pph'                           => 'nullable|numeric|min:0|max:100', 
            'riwayat_termin.*.deskripsi_termin_proyek' => 'required|string',
            'riwayat_termin.*.type_termin_proyek' => 'required|in:1,2',
            'riwayat_termin.*.payment_date_termin_proyek' => 'required|date',
            'riwayat_termin.*.attachment_file_termin_proyek' => 'nullable|file|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072', 
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

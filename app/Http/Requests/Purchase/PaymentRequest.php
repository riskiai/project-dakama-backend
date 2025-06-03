<?php

namespace App\Http\Requests\Purchase;

use App\Facades\MessageDakama;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            // format: 2025-06-03
            'tanggal_pembayaran_purchase' => 'nullable|date_format:Y-m-d',
        ];

        /** 
         * Front-end akan mengirim 1 atau lebih file dengan key  file_pembayaran[]
         * Jika hanya satu file tanpa [], Laravel tetap menangani dengan benar.
         */
        if ($this->hasFile('file_pembayaran')) {
            $rules['file_pembayaran']   = 'array';
            $rules['file_pembayaran.*'] = 'nullable|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072';
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse([
            'status'       => MessageDakama::WARNING,
            'status_code'  => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
            'message'      => $validator->errors(),
        ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);

        throw new ValidationException($validator, $response);
    }
}

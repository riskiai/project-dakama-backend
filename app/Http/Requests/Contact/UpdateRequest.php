<?php

namespace App\Http\Requests\Contact;

use App\Facades\MessageActeeve;
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
        return [
            'contact_type' => 'nullable|exists:contact_type,id',
            'name' => 'nullable|max:255',
            'address' => 'nullable|max:255',
            'bank_name' => 'nullable|max:255',
            'branch' => 'nullable|max:255',
            'account_name' => 'nullable|max:255',
            'currency' => 'nullable|max:255',
            'account_number' => 'nullable|numeric',
            'swift_code' => 'nullable|max:255',
            'attachment_npwp' => 'nullable|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072',
            'pic_name' => 'nullable|max:255',
            'phone' => 'nullable|numeric',
            'email' => 'nullable|email|max:255',
            'attachment_file' => 'nullable|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072',
        ];
    }

    /**
     * function attributes => digunakan untuk merubah attribut dari definisi awal menjadi nama baru
     *
     * @return void
     */
    public function attributes()
    {
        return [
            'attachment_npwp' => 'attachment npwp / ktp'
        ];
    }

    /**
     * function failedValidation => merupakan standar response validasi jika menggunakan `api`
     *
     * @param Validator $validator
     * @return void
     */
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

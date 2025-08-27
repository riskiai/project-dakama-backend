<?php

namespace App\Http\Requests\Purchase;

use App\Models\Purchase;
use App\Facades\MessageDakama;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'purchase_id'          => 'required|in:1,2',
            'purchase_category_id' => 'required|exists:purchase_category,id',
            'date'                 => 'required|date',
            'due_date'             => 'required|date|after_or_equal:date',

            'description'          => 'nullable|string',
            'remarks'              => 'nullable|string|max:500',

            'project_id' => 'required|exists:projects,id',

            // ── array produk ──
            'products'                     => 'nullable|array|min:1',
            'products.*.company_id'        => 'nullable|exists:companies,id',
            'products.*.product_name'      => 'nullable|string|max:255',
            'products.*.harga'             => 'nullable|numeric|min:0',
            'products.*.stok'              => 'nullable|integer|min:1',
            'products.*.ppn'               => 'nullable|numeric|min:0|max:100',
        ];

        if ($this->hasFile('attachment_file')) {
            $rules['attachment_file']      = 'array';
            $rules['attachment_file.*']    = 'nullable|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072';
        }

        // Hanya untuk Event
        // if ($this->purchase_id == 1) {
        //     $rules['project_id'] = 'required|exists:projects,id';
        // }

         // >>> Khusus EVENT: purchase_event_type wajib & valid; selain EVENT: di-drop
        $rules['purchase_event_type'] = [
            'nullable',
            'exclude_unless:purchase_id,' . Purchase::TYPE_EVENT,
            'required_if:purchase_id,' . Purchase::TYPE_EVENT,
            'integer',
            Rule::in([Purchase::TYPE_EVENT_PURCHASE_MATERIALS, Purchase::TYPE_EVENT_PURCHASE_SERVICES]),
        ];

        return $rules;
    }


    public function attributes()
    {
        return [
            'purchase_id' => 'purchase type',
            'purchase_category_id' => 'category purchase',
            'client_id' => 'client',
            'project_id' => 'project',
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

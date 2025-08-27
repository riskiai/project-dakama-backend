<?php

namespace App\Http\Requests\Purchase;

use App\Models\Purchase;
use App\Facades\MessageDakama;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
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
            // 'products'                     => 'required|array|min:1',
            // 'products.*.company_id'        => 'required|exists:companies,id',
            // 'products.*.product_name'      => 'required|string|max:255',
            // 'products.*.harga'             => 'required|numeric|min:0',
            // 'products.*.stok'              => 'required|integer|min:1',
            // 'products.*.ppn'               => 'nullable|numeric|min:0|max:100',
        ];

        $rules['products']                     = 'required_without:products_id|array|min:1';
        $rules['products.*.company_id']        = 'required_without:products_id|exists:companies,id';
        $rules['products.*.product_name']      = 'required_without:products_id|string|max:255';
        $rules['products.*.harga']             = 'required_without:products_id|numeric|min:0';
        $rules['products.*.stok']              = 'required_without:products_id|integer|min:1';
        $rules['products.*.ppn']               = 'nullable|numeric|min:0|max:100';

        $rules['products_id']                  = 'required_without:products|array|min:1';
        $rules['products_id.*']                = 'exists:purchase_products_companies,id';

        if ($this->hasFile('attachment_file')) {
            $rules['attachment_file']      = 'array';
            $rules['attachment_file.*']    = 'nullable|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072';
        }

         $rules['purchase_event_type'] = [
            'nullable',
            // kalau bukan EVENT, field ini dikeluarkan dari request (tidak akan di-mass assign)
            'exclude_unless:purchase_id,' . Purchase::TYPE_EVENT,
            // kalau EVENT, field ini wajib
            'required_if:purchase_id,' . Purchase::TYPE_EVENT,
            'integer',
            Rule::in([Purchase::TYPE_EVENT_PURCHASE_MATERIALS, Purchase::TYPE_EVENT_PURCHASE_SERVICES]),
        ];

        // Hanya untuk Event
        // if ($this->purchase_id == 1) {
        //     $rules['project_id'] = 'required|exists:projects,id';
        // }

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

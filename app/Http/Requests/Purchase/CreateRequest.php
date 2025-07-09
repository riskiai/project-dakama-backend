<?php

declare(strict_types=1);

namespace App\Http\Requests\Purchase;

use App\Facades\MessageDakama;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Validate payload for creating a new Purchase document.
 *
 * – Products dapat dikirim sebagai array objek baru (`products`) **atau**
 *   sebagai id template lama (`products_id`).
 * – Lampiran bersifat opsional.
 *
 * Custom `failedValidation()` dipertahankan agar response konsisten
 * dengan format API Dakama (status, status_code, message).
 */
class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Akses endpoint ini sudah dijaga oleh Sanctum/JWT middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            // ——— Header fields ———————————————————————————
            'purchase_id'          => ['required', Rule::in([1, 2])],
            // NB: tabel di DB bernama `purchase_category` (singular)
            'purchase_category_id' => ['required', 'exists:purchase_category,id'],
            'date'                 => ['required', 'date'],
            'due_date'             => ['required', 'date', 'after_or_equal:date'],
            'description'          => ['nullable', 'string'],
            'remarks'              => ['nullable', 'string', 'max:500'],
            'project_id'           => ['required', 'exists:projects,id'],

            // ——— New product rows —————————————————————————
            'products'                     => ['required_without:products_id', 'array', 'min:1'],
            'products.*.company_id'        => ['required_without:products_id', 'exists:companies,id'],
            'products.*.product_name'      => ['required_without:products_id', 'string', 'max:255'],
            'products.*.harga'             => ['required_without:products_id', 'numeric', 'min:0'],
            'products.*.stok'              => ['required_without:products_id', 'integer', 'min:1'],
            'products.*.ppn'               => ['nullable', 'numeric', 'min:0', 'max:100'],

            // ——— Clone from template rows ——————————————————
            'products_id'                  => ['required_without:products', 'array', 'min:1'],
            'products_id.*'                => ['exists:purchase_products_companies,id'],

            // ——— Attachments ————————————————————————————
            'attachment_file'              => ['nullable', 'array'],
            'attachment_file.*'            => ['file', 'mimes:pdf,png,jpg,jpeg,xlsx,xls,heic', 'max:3072'],
        ];
    }

    public function attributes(): array
    {
        return [
            'purchase_id'          => 'purchase type',
            'purchase_category_id' => 'category purchase',
            'project_id'           => 'project',
            'products'             => 'products',
            'products_id'          => 'template products',
            'attachment_file'      => 'attachment',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Jika frontend mengirim "products" sebagai JSON string, decode di sini.
        if ($this->filled('products') && is_string($this->products)) {
            $decoded = json_decode($this->products, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['products' => $decoded]);
            }
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = new JsonResponse([
            'status'      => MessageDakama::WARNING,
            'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
            'message'     => $validator->errors(),
        ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);

        throw new ValidationException($validator, $response);
    }
}

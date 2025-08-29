<?php

namespace App\Http\Requests\Project\SetAbsen;

use App\Facades\MessageDakama;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

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
            'location_id' => 'nullable|exists:project_has_locations,id',
            // 'user_id'    => 'required|array',
            // 'user_id.*'  => 'nullable|exists:users,id|numeric|min:1',
              'user_id'     => 'required|array|min:1',
            'user_id.*'   => [
                'required',
                'integer',
                'min:1',
                'exists:users,id',
                // Cegah user yang sama pada project yang sama (yang belum di-soft-delete)
                Rule::unique('users_project_absen', 'user_id')
                    ->where(function ($q) {
                        return $q->where('project_id', $this->project_id)
                                 ->whereNull('deleted_at');
                    }),
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => is_array($this->input('user_id')) ? $this->input('user_id') : [$this->input('user_id')],
        ]);
    }

     public function withValidator($validator)
    {
        $validator->after(function ($v) {
            // Cek duplikat di dalam payload sendiri
            $userIds = array_filter((array) $this->input('user_id', []));
            $dupe    = collect($userIds)
                        ->countBy()
                        ->filter(fn($c) => $c > 1)
                        ->keys()
                        ->all();

            if (!empty($dupe)) {
                $v->errors()->add('user_id', 'Terdapat duplikat user_id di payload: [' . implode(', ', $dupe) . '].');
            }
        });
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

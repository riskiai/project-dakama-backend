<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Facades\MessageDakama;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
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
            'company_id' => 'nullable|exists:companies,id',
            'user_id' => 'nullable|exists:users,id',      
            'tasks_id' => 'nullable|exists:tasks,id',
            'name' => 'nullable|string',
            'billing' => 'nullable|numeric',
            'cost_estimate' => 'nullable|numeric',
            'margin' => 'nullable|numeric',
            'percent' => 'nullable|numeric',
            'status_cost_progres' => 'nullable|string',
            'attachment_file' => 'nullable|mimes:pdf,png,jpg,jpeg,xlsx,xls,heic|max:3072', 
            'date' => 'nullable|date',
            'request_status_owner' => 'nullable|string',
            'status_bonus_project' => 'nullable|string',
            'harga_type_project' => 'nullable|numeric',
            'type_projects' => 'nullable|in:' . implode(',', [Project::HIK, Project::DWI]),
            'no_dokumen_project' => 'nullable|string',
                
            // Produk dan User ID harus berupa array
            // 'tasks' => 'nullable|array',
            // 'tasks_id.*' => 'nullable|exists:tasks,id|numeric|min:1',

            'operational_hour_id' => 'nullable|exists:operational_hours,id',

            'tasks_id'   => 'nullable|array',         
            'tasks_id.*' => 'nullable|exists:tasks,id',
        
            // 'user_id' => 'nullable|array',
            // 'user_id.*' => 'nullable|exists:users,id|numeric|min:1',
            'user_id' => 'nullable|array',
            'user_id.*' => 'exists:users,id|numeric|min:1',

        ];
    }

        /**
     * Menyiapkan data sebelum validasi untuk memastikan array.
     */
    protected function prepareForValidation()
    {
        // Mengubah produk_id dan user_id menjadi array jika hanya satu nilai yang diberikan
        $this->merge([
            'tasks_id' => $this->input('tasks_id') ? (is_array($this->input('tasks_id')) ? $this->input('tasks_id') : [$this->input('tasks_id')]) : [],
            // 'tasks_id' => is_array($this->input('tasks_id')) ? $this->input('tasks_id') : [$this->input('tasks_id')],
            // 'user_id' => is_array($this->input('user_id')) ? $this->input('user_id') : [$this->input('user_id')],
            'user_id' => $this->input('user_id') ? (is_array($this->input('user_id')) ? $this->input('user_id') : [$this->input('user_id')]) : [],
        ]);
    }

    /**
     * Attribute names for better error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'tasks_id' => 'tasks IDs',
            'user_id' => 'user IDs',
            'name' => 'project name',
            'billing' => 'billing amount',
            'cost_estimate' => 'cost estimate',
            'margin' => 'margin',
            'percent' => 'percent',
            'status_cost_progres' => 'status cost progress',
            'attachment_file' => 'attachment file',
            'date' => 'project date',
            'request_status_owner' => 'request status owner',
            'status_step_project' => 'status step project',
            'attachment_file_spb' => 'attachment file spb',
        ];
    }

    /**
     * Customize the response for validation failures.
     */
    protected function failedValidation(Validator $validator)
    {
        $response = new JsonResponse([
            'status' => MessageDakama::WARNING,
            'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
            'message' => $validator->errors(),
        ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);

        throw new ValidationException($validator, $response);
    }
}

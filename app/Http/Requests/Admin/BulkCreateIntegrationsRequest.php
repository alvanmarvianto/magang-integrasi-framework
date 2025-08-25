<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateIntegrationsRequest extends FormRequest
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
            'integrations' => 'required|array|min:1',
            'integrations.*.source_app_id' => 'required|exists:apps,app_id',
            'integrations.*.target_app_id' => 'required|exists:apps,app_id',
            'integrations.*.connection_type_id' => 'required|exists:connectiontypes,connection_type_id',
            'integrations.*.direction' => 'required|in:one_way,both_ways',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'integrations.min' => 'Harus ada minimal 1 integrasi.',
        ];
    }
}

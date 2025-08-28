<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppRequest extends FormRequest
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
            'app_name' => 'required|string|max:255|regex:/^[^\/]*$/',
            'description' => 'nullable|string',
            'stream_id' => 'required|exists:streams,stream_id',
            'app_type' => 'nullable|in:cots,inhouse,outsource',
            'stratification' => 'nullable|in:strategis,kritikal,umum',
            'is_module' => 'sometimes|boolean',
            'vendors' => 'array',
            'vendors.*.name' => 'required|string|regex:/^[^\/]*$/',
            'vendors.*.version' => 'nullable|string',
            'operating_systems' => 'array',
            'operating_systems.*.name' => 'required|string|regex:/^[^\/]*$/',
            'operating_systems.*.version' => 'nullable|string',
            'databases' => 'array',
            'databases.*.name' => 'required|string|regex:/^[^\/]*$/',
            'databases.*.version' => 'nullable|string',
            'languages' => 'array',
            'languages.*.name' => 'required|string|regex:/^[^\/]*$/',
            'languages.*.version' => 'nullable|string',
            'frameworks' => 'array',
            'frameworks.*.name' => 'required|string|regex:/^[^\/]*$/',
            'frameworks.*.version' => 'nullable|string',
            'middlewares' => 'array',
            'middlewares.*.name' => 'required|string|regex:/^[^\/]*$/',
            'middlewares.*.version' => 'nullable|string',
            'third_parties' => 'array',
            'third_parties.*.name' => 'required|string|regex:/^[^\/]*$/',
            'third_parties.*.version' => 'nullable|string',
            'platforms' => 'array',
            'platforms.*.name' => 'required|string|regex:/^[^\/]*$/',
            'platforms.*.version' => 'nullable|string',

            'functions' => 'sometimes|array',
            'functions.*.function_name' => 'required_with:functions|string|max:255',
            'functions.*.integration_ids' => 'required_without:functions.*.integration_id|array|min:1',
            'functions.*.integration_ids.*' => 'integer|exists:appintegrations,integration_id',
            'functions.*.integration_id' => 'nullable|integer|exists:appintegrations,integration_id',
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
            'app_name.regex' => 'Nama aplikasi tidak boleh mengandung karakter garis miring (/).',
            'vendors.*.name.regex' => 'Nama vendor tidak boleh mengandung karakter garis miring (/).',
            'operating_systems.*.name.regex' => 'Nama sistem operasi tidak boleh mengandung karakter garis miring (/).',
            'databases.*.name.regex' => 'Nama database tidak boleh mengandung karakter garis miring (/).',
            'languages.*.name.regex' => 'Nama bahasa pemrograman tidak boleh mengandung karakter garis miring (/).',
            'frameworks.*.name.regex' => 'Nama framework tidak boleh mengandung karakter garis miring (/).',
            'middlewares.*.name.regex' => 'Nama middleware tidak boleh mengandung karakter garis miring (/).',
            'third_parties.*.name.regex' => 'Nama pihak ketiga tidak boleh mengandung karakter garis miring (/).',
            'platforms.*.name.regex' => 'Nama platform tidak boleh mengandung karakter garis miring (/).',];
    }
}

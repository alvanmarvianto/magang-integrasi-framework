<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stream_id' => 'required|exists:streams,stream_id',
            'app_type' => 'nullable|in:cots,inhouse,outsource',
            'stratification' => 'nullable|in:strategis,kritikal,umum',
            'is_module' => 'sometimes|boolean',
            'vendors' => 'array',
            'vendors.*.name' => 'required|string',
            'vendors.*.version' => 'nullable|string',
            'operating_systems' => 'array',
            'operating_systems.*.name' => 'required|string',
            'operating_systems.*.version' => 'nullable|string',
            'databases' => 'array',
            'databases.*.name' => 'required|string',
            'databases.*.version' => 'nullable|string',
            'languages' => 'array',
            'languages.*.name' => 'required|string',
            'languages.*.version' => 'nullable|string',
            'frameworks' => 'array',
            'frameworks.*.name' => 'required|string',
            'frameworks.*.version' => 'nullable|string',
            'middlewares' => 'array',
            'middlewares.*.name' => 'required|string',
            'middlewares.*.version' => 'nullable|string',
            'third_parties' => 'array',
            'third_parties.*.name' => 'required|string',
            'third_parties.*.version' => 'nullable|string',
            'platforms' => 'array',
            'platforms.*.name' => 'required|string',
            'platforms.*.version' => 'nullable|string',

            // Informasi Modul section (each module can map to multiple integrations)
            'functions' => 'sometimes|array',
            'functions.*.function_name' => 'required_with:functions|string|max:255',
            // Preferred: integration_ids as an array of integration IDs
            'functions.*.integration_ids' => 'required_without:functions.*.integration_id|array|min:1',
            'functions.*.integration_ids.*' => 'integer|exists:appintegrations,integration_id',
            // Backward compatibility: single integration_id allowed if integration_ids missing
            'functions.*.integration_id' => 'nullable|integer|exists:appintegrations,integration_id',
        ];
    }
} 
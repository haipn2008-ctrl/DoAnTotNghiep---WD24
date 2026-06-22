<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|max:255',

            'date_of_birth' => 'nullable|date',

            'cccd' => 'required|max:12',

            'cccd_issue_date' => 'nullable|date',

            'cccd_issue_place' => 'nullable|max:255',

            'phone' => 'required|max:15',

            'email' => 'nullable|email',

            'address' => 'nullable|max:255',
        ];
    }
}

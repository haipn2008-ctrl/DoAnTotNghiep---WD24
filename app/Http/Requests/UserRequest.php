<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'required|max:255',

            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->ignore($userId),
            ],

            'phone' => 'nullable|max:20',

            'role_id' => 'required|exists:roles,id',

            'password' => $this->isMethod('post')
                ? 'required|min:6|confirmed'
                : 'nullable|min:6|confirmed',
        ];
    }
}

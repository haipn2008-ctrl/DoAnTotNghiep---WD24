<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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

            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(
                    fn ($query) => $query->whereIn('role_name', ['Admin', 'User'])
                ),
            ],

            'status' => [
                Rule::requiredIf($this->isMethod('put') || $this->isMethod('patch')),
                Rule::in(['pending', 'active', 'settling', 'former', 'locked', 'inactive']),
            ],

            'password' => $this->isMethod('post')
                ? ['required', 'confirmed', Password::min(8)]
                : ['nullable', 'confirmed', Password::min(8)],
        ];
    }
}

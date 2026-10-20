<?php

namespace App\Api\V1\Requests;

use App\User;
use Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        $rules = Config::get('boilerplate.store_user.validation_rules');
        // PUT user/{id} edits that user, PUT user/me the logged in one
        $id = $this->route('id') ?? $this->user('api')?->id;
        $rules['email'] = ['nullable', 'email', Rule::unique('users', 'email')->ignore($id)];

        return $rules;
    }

    public function messages()
    {
        return [
            'firstName.required' => 'Der Vorname ist erforderlich.',
            'familyName.required' => 'Der Nachname ist erforderlich.',
            'email.email' => 'Die E-Mail-Adresse ist ungültig.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
        ];
    }

    /** An empty e-mail field means "no e-mail" (the column is unique, '' would collide). */
    protected function prepareForValidation(): void
    {
        if ($this->has('email') && trim((string) $this->input('email')) === '') {
            $this->merge(['email' => null]);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function toUser(): User
    {
        return new User(
            $this->validated()
        );
    }
}

<?php

namespace App\Api\V1\Requests;

use Config;
use Illuminate\Foundation\Http\FormRequest;

class CreateUnregisteredUserRequest extends FormRequest
{
    public function rules()
    {
        $rules = Config::get('boilerplate.create_unregistered_user.validation_rules');
        $rules['email'] = ['nullable', 'email', 'unique:users,email'];

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
}

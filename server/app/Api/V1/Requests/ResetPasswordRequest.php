<?php

namespace App\Api\V1\Requests;

use Config;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.reset_password.validation_rules');
    }

    public function messages()
    {
        $policy = Config::get('boilerplate.password_policy');
        $minLength = $policy['min_length'];
        $specialChars = $policy['special_chars'];
        
        $passwordRequirements = [];
        if ($policy['require_lowercase']) $passwordRequirements[] = 'Kleinbuchstaben';
        if ($policy['require_uppercase']) $passwordRequirements[] = 'Großbuchstaben';
        if ($policy['require_digits']) $passwordRequirements[] = 'Zahlen';
        if ($policy['require_special']) $passwordRequirements[] = "Sonderzeichen ({$specialChars})";
        
        $requirementsText = implode(', ', $passwordRequirements);
        
        return [
            'token.required' => 'Ein gültiger Reset-Token ist erforderlich.',
            'email.required' => 'Die E-Mail ist erforderlich.',
            'email.email' => 'Die E-Mail muss eine gültige E-Mail-Adresse sein.',
            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => "Das Passwort muss mindestens {$minLength} Zeichen lang sein.",
            'password.regex' => "Das Passwort muss {$requirementsText} enthalten.",
            'password.confirmed' => 'Die Passwörter stimmen nicht überein.',
        ];
    }

    public function authorize()
    {
        return true;
    }
}

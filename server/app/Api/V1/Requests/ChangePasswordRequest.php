<?php

namespace App\Api\V1\Requests;

use Config;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.change_password.validation_rules');
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
            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => "Das Passwort muss mindestens {$minLength} Zeichen lang sein.",
            'password.regex' => "Das Passwort muss {$requirementsText} enthalten.",
        ];
    }

    public function authorize()
    {
        return true;
    }
}

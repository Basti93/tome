<?php

namespace App\Api\V1\Requests;

use App\User;
use Config;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.store_user.validation_rules');
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

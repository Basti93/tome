<?php

namespace App\Api\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;
use App\User;

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

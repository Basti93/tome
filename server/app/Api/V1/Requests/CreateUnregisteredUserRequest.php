<?php

namespace App\Api\V1\Requests;

use Config;
use Illuminate\Foundation\Http\FormRequest;
use App\Training;

class CreateUnregisteredUserRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.create_unregistered_user.validation_rules');
    }

    public function authorize()
    {
        return true;
    }

}

<?php

namespace App\Api\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.store_location.validation_rules');
    }

    public function authorize()
    {
        return true;
    }

}


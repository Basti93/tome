<?php

namespace App\Api\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.store_group.validation_rules');
    }

    public function authorize()
    {
        return true;
    }

}


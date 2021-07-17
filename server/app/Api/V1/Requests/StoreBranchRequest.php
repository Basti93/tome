<?php

namespace App\Api\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.store_branch.validation_rules');
    }

    public function authorize()
    {
        return true;
    }

}


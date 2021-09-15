<?php

namespace App\Api\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;

class StoreUserAbsenceRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.store_user_absence.validation_rules');
    }

    public function authorize()
    {
        return true;
    }

}

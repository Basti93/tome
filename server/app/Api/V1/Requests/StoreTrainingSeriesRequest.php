<?php

namespace App\Api\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;
use App\Training;

class StoreTrainingSeriesRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.store_training_series.validation_rules');
    }

    public function authorize()
    {
        return true;
    }

}

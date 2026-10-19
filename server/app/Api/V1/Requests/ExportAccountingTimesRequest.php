<?php

namespace App\Api\V1\Requests;

use Config;
use Illuminate\Foundation\Http\FormRequest;

class ExportAccountingTimesRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.export_accounting_times.validation_rules');
    }

    public function authorize()
    {
        return true;
    }
}

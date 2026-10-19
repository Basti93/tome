<?php

namespace App\Api\V1\Requests;

use Config;
use DateTime;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Public endpoint (no login): only plausible, future absences of at most a year are accepted.
 */
class StoreUserAbsenceRequest extends FormRequest
{
    public const MAX_DAYS = 365;

    public function rules()
    {
        return Config::get('boilerplate.store_user_absence.validation_rules');
    }

    public function authorize()
    {
        return true;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $start = DateTime::createFromFormat(DateTime::ISO8601, (string) $this->input('absenceStart'));
            $end = DateTime::createFromFormat(DateTime::ISO8601, (string) $this->input('absenceEnd'));
            if ($start === false) {
                $validator->errors()->add('absenceStart', 'Das Startdatum ist ungültig.');
            }
            if ($end === false) {
                $validator->errors()->add('absenceEnd', 'Das Enddatum ist ungültig.');
            }
            if ($start === false || $end === false) {
                return;
            }

            if ($start->format('Y-m-d') < now()->format('Y-m-d')) {
                $validator->errors()->add('absenceStart', 'Das Startdatum darf nicht in der Vergangenheit liegen.');
            }
            if ($end < $start) {
                $validator->errors()->add('absenceEnd', 'Das Enddatum darf nicht vor dem Startdatum liegen.');
            } elseif ($start->diff($end)->days > self::MAX_DAYS) {
                $validator->errors()->add('absenceEnd', 'Die Abwesenheit darf höchstens '.self::MAX_DAYS.' Tage dauern.');
            }
        }];
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\TrainingTrainerExport;
use DateTime;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;

class TrainingAccountingExportController extends Controller
{
    public function exportAccountingTimes($request)
    {
        $from = DateTime::createFromFormat(DateTime::ISO8601, $request->input('from'));
        $to = DateTime::createFromFormat(DateTime::ISO8601, $request->input('to'));
        $userId = $request->input('userId');
        $user = User::findOrFail($userId);

        return Excel::download(new TrainingTrainerExport($userId, $from, $to), 'ul_abrechnung_'.$user.firstName.'_'.$user.familyName.'_'.$from.'_'.$to.'.xlsx');
    }

}

<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TrainerHours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "Meine Stunden": the logged-in trainer's own accounting hours of a year. */
class TrainerHoursController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function me(Request $request, TrainerHours $hours): JsonResponse
    {
        $user = $request->user('api');
        abort_unless($user->hasAnyRole(['trainer', 'admin']), 403, 'Nur für Trainer.');
        $data = $request->validate(['year' => 'sometimes|integer|between:2000,2100']);

        return response()->json($hours->forYear($user, (int) ($data['year'] ?? now()->year)));
    }
}

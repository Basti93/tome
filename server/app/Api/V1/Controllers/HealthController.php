<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Liveness/readiness probe: a trivial DB round trip. Never reveals details on failure.
     */
    public function show()
    {
        try {
            DB::select('select 1');
        } catch (\Throwable) {
            return response()->json(['status' => 'unavailable'], 503);
        }

        return response()->json(['status' => 'ok']);
    }
}

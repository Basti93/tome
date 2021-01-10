<?php

namespace App\Http\Controllers;

use App\Training;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MacsiDigital\Zoom\Support\Entry as Zoom;

class ZoomController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $zoom = new Zoom();
        $user = $zoom::user()->first();
        return response()->json($user->meetings()->all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Training $training)
    {
        $zoom = new Zoom();
        $user = $zoom::user()->first();

        $diff = $training->start->diff($training->end);
        $minutes = ($diff->days * 24 * 60) +
            ($diff->h * 60) + $diff->i;

        $meeting = Zoom::meeting()->make([
            'topic' => 'SSC-Training (Erstellt mit SSC-TP)',
            'type' => 2,
            'timezone' => 'Europe/Berlin',
            'start_time' => $training->start->format('Y-m-d\TH:i:s.00'),
            'duration' => $minutes
        ]);

        $savedMeeting = $user->meetings()->save($meeting);
        $training->zoom_id = $savedMeeting->id;
        if (empty($training->comment)) {
            $training->comment = "Zum Zoom-Meeting: ".$savedMeeting->join_url;
        } else {
            $training->comment = $training->comment."\n\rZum Zoom-Meeting: ".$savedMeeting->join_url;
        }
        $training->save();
    }

    public function updateOrCreate(Training $training)
    {
        $zoom = new Zoom();

        if (!empty($training->zoom_id)) {
            $meeting = $zoom->meeting()->find($training->zoom_id);
            if ($meeting) {
                $diff = $training->start->diff($training->end);
                $minutes = ($diff->days * 24 * 60) +
                    ($diff->h * 60) + $diff->i;

                $meeting->update([
                    'start_time' => $training->start->format('Y-m-d\TH:i:s.00'),
                    'duration' => $minutes
                ]);
            }
        } else {
            $this->create($training);
        }

    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Training $training)
    {
        $zoom = new Zoom();
        if (!empty($training->zoom_id)) {
            $meeting = $zoom->meeting()->find($training->zoom_id);
            if ($meeting) {
                $meeting->delete();
            }
        }
    }
}

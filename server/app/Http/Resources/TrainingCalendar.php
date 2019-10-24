<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DateTime;
use Illuminate\Support\Facades\Log;

class TrainingCalendar extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->id < 0)
        Log::info($request);
        return [
            'id' => $this->id,
            'start' => DateTime::createFromFormat('Y-m-d H:i:s', $this->start)->format(DateTime::ATOM),
            'end' => DateTime::createFromFormat('Y-m-d H:i:s', $this->end)->format(DateTime::ATOM),
            'locationId' => $this->location_id,
            'groupIds' => $this->group_ids,
            'trainerIds' => $this->trainer_ids,
            'participants' => $this->trainingParticipation,
            'contentIds' => $this->content_ids,
            'comment' => $this->comment,
            'prepared' => $this->prepared,
            'evaluated' => $this->evaluated,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use DateTime;

class TrainingSeries extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'startTime' => substr($this->startTime,0,5),
            'endTime' => substr($this->endTime,0,5),
            'weekdays' => json_decode($this->weekdays),
            'locationId' => $this->location_id,
            'groupIds' => $this->group_ids,
            'trainerIds' => $this->trainer_ids,
            'contentIds' => $this->content_ids,
            'comment' => $this->comment,
            'defer_until' => $this->defer_until ? DateTime::createFromFormat('Y-m-d H:i:s', $this->defer_until)->format(DateTime::ATOM) : null,
            'automaticAttend' => $this->automatic_attend,
        ];
    }
}

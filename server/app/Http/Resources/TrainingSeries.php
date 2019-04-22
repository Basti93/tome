<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'weekdays' => json_decode($this->weekdays),
            'locationId' => $this->location_id,
            'groupIds' => $this->group_ids,
            'trainerIds' => $this->trainer_ids,
            'contentIds' => $this->content_ids,
            'comment' => $this->comment,
            'active' => $this->active,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Training extends JsonResource
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
            'start' => $this->start,
            'end' => $this->end,
            'locationId' => $this->location_id,
            'groupIds' => $this->group_ids,
            'trainerIds' => $this->trainer_ids,
            'participantIds' => $this->participant_ids,
            'contentIds' => $this->content_ids,
            'comment' => $this->comment,
        ];
    }
}

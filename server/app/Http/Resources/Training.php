<?php

namespace App\Http\Resources;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Training extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $participation = $this->trainingParticipation;

        return [
            'id' => $this->id,
            'start' => DateTime::createFromFormat('Y-m-d H:i:s', $this->start)->format(DateTime::ATOM),
            'end' => DateTime::createFromFormat('Y-m-d H:i:s', $this->end)->format(DateTime::ATOM),
            'locationId' => $this->location_id,
            'groupIds' => $this->group_ids,
            'trainerIds' => $this->trainer_ids,
            // without login (public check-in page) only the ids and the attendance flag, no cancel reasons
            'participants' => auth('api')->check()
                ? $participation
                : $participation->map(fn ($p) => [
                    'attend' => $p->attend,
                    'trainingId' => $p->training_id,
                    'userId' => $p->user_id,
                ])->values(),
            'contentIds' => $this->content_ids,
            'comment' => $this->comment,
            'prepared' => $this->prepared,
            'evaluated' => $this->evaluated,
            'automaticAttend' => $this->automatic_attend,
        ];
    }
}

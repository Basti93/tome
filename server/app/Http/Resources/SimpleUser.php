<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SimpleUser extends JsonResource
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
            'firstName' => $this->firstName,
            'familyName' => $this->familyName,
            'groupIds' => $this->group_ids,
            'profileImageName' => $this->profile_image_name,
            'trainerBranchIds' => $this->trainerBranchIds,
            'active' => $this->active,
        ];
    }
}

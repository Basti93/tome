<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Group extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'branchId' => $this->branch_id,
            // the member list of a group is only visible to logged in users
            'userIds' => auth('api')->check() ? $this->user_ids : [],
        ];
    }
}

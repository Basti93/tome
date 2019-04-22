<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NonApprovedUser extends JsonResource
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
            'createdAt' => DateTime::createFromFormat('Y-m-d H:i:s', $this->created_at)->format(DateTime::ATOM),
        ];
    }
}

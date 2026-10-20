<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class SimpleUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // These endpoints are public (child check-in): without login only "Vorname M." and no photo.
        $authenticated = auth('api')->check();
        $familyName = $this->familyName;

        $data = [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'familyName' => $authenticated ? $familyName : self::initial($familyName),
            'groupIds' => $this->group_ids,
            'profileImageName' => $authenticated ? $this->profile_image_name : null,
            'trainerBranchIds' => $this->trainerBranchIds,
            'active' => $this->active,
        ];

        // The birthdate (birthday hint in the trainer views) is only for logged in trainers and admins: the key does
        // not exist for anybody else.
        if ($authenticated && auth('api')->user()->hasAnyRole(['trainer', 'admin'])) {
            $birthdate = $this->resource->birthdate;
            $data['birthdate'] = $birthdate ? Carbon::parse($birthdate)->format('Y-m-d') : null;
        }

        return $data;
    }

    /** First letter plus a dot ("Meier" -> "M."); an empty name stays empty. */
    private static function initial(?string $familyName): ?string
    {
        if ($familyName === null || trim($familyName) === '') {
            return $familyName;
        }

        return mb_strtoupper(mb_substr(trim($familyName), 0, 1)).'.';
    }
}

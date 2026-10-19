<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'familyName' => $authenticated ? $familyName : self::initial($familyName),
            'groupIds' => $this->group_ids,
            'profileImageName' => $authenticated ? $this->profile_image_name : null,
            'trainerBranchIds' => $this->trainerBranchIds,
            'active' => $this->active,
        ];
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

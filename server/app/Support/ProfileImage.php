<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Naming rules and cleanup for profile images, which live on the `public` disk
 * (storage/app/public/profile_images/<random>.<ext>).
 */
class ProfileImage
{
    public const DISK = 'public';

    public const DIRECTORY = 'profile_images';

    /** Validation / deletion whitelist: new uploads are 40 random chars + .jpg, legacy files may be .jpeg/.png. */
    public const PATTERN = '/^profile_images\/[A-Za-z0-9]{20,64}\.(jpg|jpeg|png)$/';

    public static function randomName(): string
    {
        return Str::random(40);
    }

    public static function isValidName(?string $name): bool
    {
        return $name !== null && preg_match(self::PATTERN, $name) === 1;
    }

    /** Deletes the file if (and only if) the name matches the whitelist pattern. */
    public static function deleteIfValid(?string $name): bool
    {
        if (! self::isValidName($name)) {
            return false;
        }

        return Storage::disk(self::DISK)->delete($name);
    }
}

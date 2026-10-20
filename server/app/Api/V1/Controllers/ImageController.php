<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ProfileImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class ImageController extends Controller
{
    /**
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Stores a profile image.
     *
     * The upload is never kept as is: it is decoded, scaled to a height of 400px (aspect ratio
     * preserved) and re-encoded as JPEG under a random name on the `public` disk
     * (storage/app/public/profile_images, served through the `public/storage` symlink at
     * `/api/storage/<imageUrl>`). Nothing is written to a web root such as `public_app`.
     */
    public function uploadProfileImage(Request $request)
    {
        if (! $request->file('profile_image')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file provided',
            ], 400);
        }

        // standard 422 {message, errors: {profile_image: [...]}}
        Validator::make($request->all(), [
            'profile_image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:min_width=50,min_height=50,max_width=8000,max_height=8000',
            ],
        ])->validate();

        $path = ProfileImage::DIRECTORY.'/'.ProfileImage::randomName().'.jpg';
        $disk = Storage::disk(ProfileImage::DISK);

        try {
            $manager = new ImageManager(new Driver);
            $encoded = $manager->decodePath($request->file('profile_image')->getRealPath())
                ->scale(height: 400)
                ->encodeUsingFormat(Format::JPEG, quality: 80);

            if (! $disk->put($path, $encoded->toString())) {
                throw new \RuntimeException('Could not write the profile image');
            }

            return response()->json([
                'status' => 'ok',
                'imageUrl' => $path,
            ], 201);
        } catch (\Throwable $e) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
            Log::error('Profile image upload failed: '.$e::class);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload image',
            ], 500);
        }
    }
}

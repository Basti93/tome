<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageController extends Controller
{
    /**
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    public function uploadProfileImage(Request $request)
    {
        $file = $request->file("profile_image");
        if (!$file) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file provided'
            ], 400);
        }

        try {
            // Ensure directory exists
            Storage::disk('public_app')->makeDirectory('profile_images', 0755, true);

            // Store the file
            $path = Storage::disk('public_app')->putFile('profile_images', $file);
            $fullPath = Storage::disk('public_app')->path($path);

            // Process image using Intervention Image v3 with explicit GD driver
            $manager = new ImageManager(new Driver());
            $manager->read($fullPath)
                ->resize(height: 400, width: null)
                ->save($fullPath, quality: 65);

            Log::info("Image uploaded: " . $fullPath);
            return response()->json([
                'status' => 'ok',
                'imageUrl' => $path
            ], 201);
        } catch (\Exception $e) {
            Log::error("Image upload error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload image'
            ], 500);
        }
    }

}

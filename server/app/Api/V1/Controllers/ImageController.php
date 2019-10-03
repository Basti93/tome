<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

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
        $path = Storage::disk('public')->putFile('profile_images', $request->file("profile_image"));

        Image::make(Storage::disk('public')->path($path))
        ->resize(null, 400, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('jpg', 65)
        ->save();

        Log::info("Path: ".Storage::disk('public')->path($path));
        return response()->json([
            'status' => 'ok',
            'imageUrl' => $path
        ], 201);
    }

}

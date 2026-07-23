<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    public function show(Photo $photo): StreamedResponse
    {
        Gate::authorize("view", $photo);

        return Storage::disk(config("utility.photo_disk"))->response($photo->path, $photo->original_filename, [
            "Content-Type" => $photo->mime_type,
        ]);
    }
}

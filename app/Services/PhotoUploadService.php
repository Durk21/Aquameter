<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class PhotoUploadService
{
    /**
     * Store already-validated uploaded files against a parent model on
     * the private disk — never public — and record who uploaded them.
     *
     * @param  UploadedFile[]  $files
     */
    public static function store(Model $photoable, array $files, User $uploader): void
    {
        foreach ($files as $file) {
            $path = $file->store("photos", "local");

            Photo::create([
                "photoable_type" => $photoable::class,
                "photoable_id" => $photoable->id,
                "uploaded_by" => $uploader->id,
                "path" => $path,
                "original_filename" => $file->getClientOriginalName(),
                "mime_type" => $file->getMimeType(),
                "size" => $file->getSize(),
            ]);
        }
    }

    public static function validationRules(): array
    {
        return [
            "photos" => "nullable|array|max:".config("utility.max_photos_per_upload"),
            "photos.*" => [
                "file",
                "image",
                "mimes:".implode(",", config("utility.photo_mimes")),
                "max:".config("utility.max_photo_size_kb"),
            ],
        ];
    }
}

<?php

namespace App\Services\Courts;

use App\Models\Court;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores and replaces court photos on the public disk.
 *
 * Only the file path is written to the database. The image itself lives in
 * storage so the courts table stays small and the file is served by the web
 * server rather than read through PHP on every page.
 */
class UploadCourtImage
{
    private const MAX_KILOBYTES = 4096;

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:'.self::MAX_KILOBYTES,
                'dimensions:min_width=320,min_height=240',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'image.image' => 'The court photo must be an image file.',
            'image.mimes' => 'The court photo must be a JPG, PNG, or WEBP file.',
            'image.max' => 'The court photo may not be larger than 4 MB.',
            'image.dimensions' => 'The court photo must be at least 320x240 pixels.',
        ];
    }

    /**
     * Persist an uploaded photo and return its stored path.
     */
    public function handle(UploadedFile $file): string
    {
        $original = basename($file->getClientOriginalName());
        $base = pathinfo($original, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION)) ?: 'jpg';

        $slug = Str::limit(Str::slug($base), 60, '');
        $name = ($slug === '' ? 'court' : $slug).'-'.Str::random(8).'.'.$extension;

        return $file->storeAs(self::directory(), $name, ['disk' => Court::IMAGE_DISK]);
    }

    /**
     * Remove a previously stored photo, ignoring paths outside our directory.
     */
    public function remove(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, self::directory().'/')) {
            return;
        }

        Storage::disk(Court::IMAGE_DISK)->delete($path);
    }

    private static function directory(): string
    {
        return Court::IMAGE_DIRECTORY;
    }
}

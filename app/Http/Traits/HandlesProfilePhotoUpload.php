<?php

namespace App\Http\Traits;

use App\Support\ImageConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

trait HandlesProfilePhotoUpload
{
    protected function storeProfilePhoto(UploadedFile $file, string $directory): string
    {
        return $this->convertAndStorePhoto($file, $directory);
    }

    protected function deleteProfilePhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function replaceProfilePhoto(UploadedFile $file, string $directory, ?string $existingPath = null): string
    {
        $this->deleteProfilePhoto($existingPath);

        return $this->convertAndStorePhoto($file, $directory);
    }

    protected function convertAndStorePhoto(UploadedFile $file, string $directory): string
    {
        $quality = (int) config('school.profile_photo_webp_quality', 88);
        $quality = max(80, min(95, $quality));

        try {
            return ImageConverter::storeAsWebp($file, $directory, 'public', $quality);
        } catch (Throwable $exception) {
            Log::warning('Profile photo WebP conversion failed, storing original file.', [
                'directory' => $directory,
                'mime' => $file->getMimeType(),
                'quality' => $quality,
                'message' => $exception->getMessage(),
            ]);

            return $file->store($directory, 'public');
        }
    }
}

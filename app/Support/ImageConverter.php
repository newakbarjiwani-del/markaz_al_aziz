<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageConverter
{
    public static function storeAsWebp(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        int $quality = 85,
    ): string {
        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.webp';

        Storage::disk($disk)->put(
            $path,
            self::toWebpContents($file, $quality),
        );

        return $path;
    }

    public static function toWebpContents(UploadedFile $file, int $quality = 85): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('WebP conversion is not supported on this server.');
        }

        $quality = max(0, min(100, $quality));
        $image = self::createImage($file);

        ob_start();
        $saved = imagewebp($image, null, $quality);
        imagedestroy($image);

        if (! $saved) {
            ob_end_clean();
            throw new RuntimeException('Failed to convert image to WebP.');
        }

        $contents = ob_get_clean();

        if ($contents === false || $contents === '') {
            throw new RuntimeException('Failed to read converted WebP image.');
        }

        return $contents;
    }

    private static function createImage(UploadedFile $file): \GdImage
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw new RuntimeException('Uploaded image is not readable.');
        }

        $mime = strtolower($file->getMimeType() ?? '');

        $image = match (true) {
            in_array($mime, ['image/jpeg', 'image/jpg'], true) => imagecreatefromjpeg($path),
            $mime === 'image/png' => imagecreatefrompng($path),
            $mime === 'image/webp' => imagecreatefromwebp($path),
            default => throw new RuntimeException("Unsupported image type: {$mime}"),
        };

        if ($image === false) {
            throw new RuntimeException('Failed to decode uploaded image.');
        }

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        return $image;
    }
}

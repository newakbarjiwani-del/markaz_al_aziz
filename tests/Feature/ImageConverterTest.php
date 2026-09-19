<?php

use App\Support\ImageConverter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeTestJpeg(int $width = 200, int $height = 200): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    $background = imagecolorallocate($image, 67, 97, 55);
    imagefill($image, 0, 0, $background);

    $path = tempnam(sys_get_temp_dir(), 'webp-test-').'.jpg';
    imagejpeg($image, $path, 90);
    imagedestroy($image);

    return new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true);
}

test('uploaded jpeg is stored as webp', function () {
    Storage::fake('public');

    $path = ImageConverter::storeAsWebp(makeTestJpeg(), 'photos/siswa');

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);

    $stored = Storage::disk('public')->get($path);
    expect(str_starts_with($stored, 'RIFF'))->toBeTrue();
    expect(str_contains(substr($stored, 8, 4), 'WEBP'))->toBeTrue();
});

test('uploaded png with transparency is stored as webp', function () {
    Storage::fake('public');

    $image = imagecreatetruecolor(200, 200);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);

    $temp = tempnam(sys_get_temp_dir(), 'webp-test-').'.png';
    imagepng($image, $temp);
    imagedestroy($image);

    $file = new UploadedFile($temp, 'photo.png', 'image/png', null, true);
    $path = ImageConverter::storeAsWebp($file, 'photos/guru');

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
})->skip(! function_exists('imagepng'), 'GD PNG support unavailable');

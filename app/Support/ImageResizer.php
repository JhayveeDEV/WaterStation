<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Resizes an uploaded product photo down to a sane max width before it's
 * stored, so the shop page isn't shipping multi-megabyte camera photos to
 * every visitor. Uses plain GD (no extra composer package) and fails open —
 * if GD isn't available or the image can't be decoded, the original upload
 * is stored as-is rather than the request failing.
 */
class ImageResizer
{
    private const MAX_WIDTH = 800;

    private const JPEG_QUALITY = 80;

    /** Returns the resized file contents, or null to signal "store the original unchanged". */
    public static function resize(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $source = imagecreatefromstring(file_get_contents($file->getRealPath()));

            if (! $source) {
                return null;
            }

            $width = imagesx($source);
            $height = imagesy($source);

            if ($width <= self::MAX_WIDTH) {
                imagedestroy($source);

                return null; // already small enough, no need to touch it
            }

            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) round($height * ($newWidth / $width));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            imagejpeg($resized, null, self::JPEG_QUALITY);
            $contents = ob_get_clean();

            imagedestroy($source);
            imagedestroy($resized);

            return $contents ?: null;
        } catch (\Throwable $e) {
            Log::warning('Product image resize failed, storing original', ['error' => $e->getMessage()]);

            return null;
        }
    }
}

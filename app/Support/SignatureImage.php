<?php

namespace App\Support;

/**
 * Turns a drawn or uploaded signature into a clean PNG: decoded and
 * re-encoded (dropping metadata and anything that is not pixels), near-white
 * background made transparent, cropped to the ink and size-limited.
 */
class SignatureImage
{
    public const MAX_WIDTH = 900;

    public const MAX_HEIGHT = 300;

    /**
     * @return string|null PNG bytes, or null when the image is unusable or blank
     */
    public static function normalize(string $bytes): ?string
    {
        $info = @getimagesizefromstring($bytes);

        // PNG/JPEG only, and refuse huge dimensions before decoding.
        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) || $info[0] > 6000 || $info[1] > 6000) {
            return null;
        }

        $source = @imagecreatefromstring($bytes);

        if (! $source) {
            return null;
        }

        $scale = min(1, self::MAX_WIDTH / $info[0], self::MAX_HEIGHT / $info[1]);
        $width = max(1, (int) round($info[0] * $scale));
        $height = max(1, (int) round($info[1] * $scale));

        $image = self::transparentCanvas($width, $height);
        imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        [$left, $top, $right, $bottom] = [$width, $height, -1, -1];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $lightest = min(($rgba >> 16) & 0xFF, ($rgba >> 8) & 0xFF, $rgba & 0xFF);

                // Transparent or near-white (paper) pixels are background.
                if ($alpha > 100 || $lightest > 215) {
                    imagesetpixel($image, $x, $y, $transparent);

                    continue;
                }

                $left = min($left, $x);
                $right = max($right, $x);
                $top = min($top, $y);
                $bottom = max($bottom, $y);
            }
        }

        if ($right < 0) {
            return null;
        }

        // Crop to the ink with a small margin.
        $pad = 6;
        $cropX = max(0, $left - $pad);
        $cropY = max(0, $top - $pad);
        $cropWidth = min($width, $right + $pad + 1) - $cropX;
        $cropHeight = min($height, $bottom + $pad + 1) - $cropY;

        $cropped = self::transparentCanvas($cropWidth, $cropHeight);
        imagecopy($cropped, $image, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);

        ob_start();
        imagepng($cropped);

        return ob_get_clean();
    }

    private static function transparentCanvas(int $width, int $height): \GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 255, 255, 255, 127));

        return $canvas;
    }
}

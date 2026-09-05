<?php

namespace App\Support\Docs;

/**
 * Draws numbered callout badges onto a raw screenshot at given pixel
 * coordinates, so "annotated screenshots" is a repeatable script rather than
 * a manual per-image editing pass — at doc-site volume (dozens of images,
 * regenerated whenever the UI changes) that's the only way this stays
 * maintainable. Uses GD only (bundled with PHP) since the app has no other
 * image-processing dependency and no Node/build pipeline to lean on.
 */
class ScreenshotAnnotator
{
    /**
     * @param string $sourcePath Raw screenshot (PNG/JPEG) from the browser capture.
     * @param string $destPath   Where to write the annotated PNG.
     * @param array<int, array{x:int,y:int,label:int|string}> $points
     *        Badge centre points, in the source image's own pixel coordinates.
     */
    public static function annotate(string $sourcePath, string $destPath, array $points): void
    {
        $image = static::load($sourcePath);

        $badgeColor = imagecolorallocate($image, 26, 107, 60); // ACM green
        $ringColor  = imagecolorallocate($image, 255, 255, 255);
        $textColor  = imagecolorallocate($image, 255, 255, 255);
        $radius     = 14;
        $font       = 5; // largest built-in GD font — no external font file needed

        foreach ($points as $point) {
            $x = (int) round($point['x']);
            $y = (int) round($point['y']);
            $label = (string) $point['label'];

            imagefilledellipse($image, $x, $y, $radius * 2, $radius * 2, $badgeColor);
            imageellipse($image, $x, $y, $radius * 2 + 2, $radius * 2 + 2, $ringColor);

            $textWidth  = imagefontwidth($font) * strlen($label);
            $textHeight = imagefontheight($font);
            imagestring($image, $font, $x - intdiv($textWidth, 2), $y - intdiv($textHeight, 2), $label, $textColor);
        }

        if (! is_dir(dirname($destPath))) {
            mkdir(dirname($destPath), 0755, true);
        }

        imagepng($image, $destPath);
        imagedestroy($image);
    }

    /** Copy a raw screenshot through unannotated (for full-page "here's the screen" shots with no callouts). */
    public static function copyPlain(string $sourcePath, string $destPath): void
    {
        if (! is_dir(dirname($destPath))) {
            mkdir(dirname($destPath), 0755, true);
        }

        copy($sourcePath, $destPath);
    }

    private static function load(string $path)
    {
        $info = getimagesize($path);

        if ($info === false) {
            throw new \RuntimeException("Not a readable image: {$path}");
        }

        return match ($info[2]) {
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            default        => throw new \RuntimeException("Unsupported screenshot format: {$path}"),
        };
    }
}

<?php

namespace Zenstruck\Filesystem\Feature\Image;

use Zenstruck\Filesystem\Feature\ImageTransformer;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Util\TempFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements ImageTransformer<\GdImage>
 */
final class GdImageFeature implements ImageTransformer
{
    public function __construct()
    {
        if (!\class_exists(\GdImage::class)) {
            throw new \LogicException('GD extension not available.');
        }
    }

    public function transform(Image $image, callable $manipulator, array $options): \SplFileInfo
    {
        $gdImage = $manipulator(\imagecreatefromstring($image->contents()) ?: throw new \RuntimeException('todo'));
        if (!$gdImage instanceof \GdImage) {
            throw new \LogicException('Manipulator callback must return a GdImage object.');
        }

        /** @var string&callable $function */
        $function = match ($options['format'] ?? $image->guessExtension()) {
            'png' => 'imagepng',
            'jpg', 'jpeg' => 'imagejpeg',
            'gif' => 'imagegif',
            'webp' => 'imagewebp',
            'avif' => 'imageavif',
            default => match ($image->mimeType()) {
                'image/png' => 'imagepng',
                'image/jpeg' => 'imagejpeg',
                'image/gif' => 'imagegif',
                'image/webp' => 'imagewebp',
                'image/avif' => 'imageavif',
                default => throw new \LogicException(\sprintf('Unable to determine image mime-type for "%s".', $image->path())),
            }
        };

        if (!\function_exists($function)) {
            throw new \LogicException(\sprintf('The "%s" gd extension function is not available.', $function));
        }

        $function($gdImage, (string) $file = new TempFile());

        return $file->refresh();
    }
}

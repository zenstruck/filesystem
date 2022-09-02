<?php

namespace Zenstruck\Filesystem\Feature\Image;

use Zenstruck\Filesystem\Feature\ImageTransformer;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Util\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 *
 * @implements ImageTransformer<\Imagick>
 */
final class ImagickImageFeature implements ImageTransformer
{
    public function __construct()
    {
        if (!\class_exists(\Imagick::class)) {
            throw new \LogicException('Imagick extension not available.');
        }
    }

    public function transform(Image $image, callable $manipulator, array $options): \SplFileInfo
    {
        $imagick = new \Imagick();
        $imagick->readImageBlob($image->contents());

        $imagick = $manipulator($imagick);

        if (!$imagick instanceof \Imagick) {
            throw new \LogicException('Manipulator callback must return an Imagick object.');
        }

        if (isset($options['format'])) {
            $imagick->setImageFormat($options['format']);
        }

        $imagick->writeImage((string) $file = new TempFile());

        return $file->refresh();
    }
}

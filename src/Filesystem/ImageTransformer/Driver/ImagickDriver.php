<?php
declare(strict_types=1);

namespace Zenstruck\Filesystem\ImageTransformer\Driver;

use BadMethodCallException;
use Imagick;
use ImagickException;
use Zenstruck\Filesystem\ImageTransformer\Driver;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class ImagickDriver implements Driver
{
    public function __construct()
    {
        if (!class_exists(Imagick::class)) {
            throw new BadMethodCallException("Imagick class in unavailable. Install `ext-imagick` to use this driver.");
        }
    }

    /**
     * @throws ImagickException
     */
    public function loadFromImage(Image $image): mixed
    {
        $imagick = new Imagick();

        if (!$imagick->readImageBlob($image->contents(), $image->name())) {
            throw new ImagickException(sprintf("Could not read from image at %s", $image->path()));
        }

        return $imagick;
    }

    /**
     * @throws ImagickException
     */
    public function getContents(mixed $resource): mixed
    {
        if (!$resource instanceof Imagick) {
            throw new BadMethodCallException(sprintf("Wrong data type returned from tranformation. %s was expected, got %s", Imagick::class, is_object($resource) ? $resource::class : $resource));
        }

        return $resource->getImageBlob();
    }
}

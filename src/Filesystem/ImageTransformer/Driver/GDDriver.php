<?php

namespace Zenstruck\Filesystem\ImageTransformer\Driver;

use BadMethodCallException;
use Exception;
use GdImage;
use Zenstruck\Filesystem\ImageTransformer\Driver;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class GDDriver implements Driver
{
    /**
     * @throws Exception
     */
    public function loadFromImage(Image $image): GdImage
    {
        // TODO: throw something meaningful on fail
        return imagecreatefromstring($image->contents()) ?: throw new Exception();
    }

    public function getContents(mixed $resource): mixed
    {
        if (!$resource instanceof GdImage) {
            throw new BadMethodCallException(sprintf("Wrong data type returned from tranformation. %s was expected, got %s", GdImage::class, is_object($resource) ? $resource::class : $resource));
        }

        // TODO: Better recognise image format
        return imagewebp($resource);
    }
}

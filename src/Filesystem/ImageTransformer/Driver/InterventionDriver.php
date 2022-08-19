<?php

namespace Zenstruck\Filesystem\ImageTransformer\Driver;

use BadMethodCallException;
use Intervention\Image\Image as InterventionImage;
use Intervention\Image\ImageManagerStatic;
use Zenstruck\Filesystem\ImageTransformer\Driver;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 * TODO: Inject ImageManager
 */
class InterventionDriver implements Driver
{
    public function loadFromImage(Image $image): InterventionImage
    {
        return ImageManagerStatic::make($image->contents());
    }

    public function getContents(mixed $resource): string
    {
        if (!$resource instanceof InterventionImage) {
            throw new BadMethodCallException(sprintf("Wrong data type returned from tranformation. %s was expected, got %s", InterventionImage::class, is_object($resource) ? $resource::class : $resource));
        }

        return (string) $resource->encode();
    }
}

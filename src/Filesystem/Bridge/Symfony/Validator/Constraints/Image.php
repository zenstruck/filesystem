<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Image as BaseImage;
use Zenstruck\Filesystem\Bridge\Symfony\Validator\ImageValidator;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Image extends BaseImage
{
    public function validatedBy(): string
    {
        return ImageValidator::class;
    }
}

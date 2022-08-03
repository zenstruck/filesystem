<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\Validator\Constraints;

use Symfony\Component\Validator\Constraints\File as BaseFile;
use Zenstruck\Filesystem\Bridge\Symfony\Validator\FileValidator;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class File extends BaseFile
{
    public function validatedBy(): string
    {
        return FileValidator::class;
    }
}

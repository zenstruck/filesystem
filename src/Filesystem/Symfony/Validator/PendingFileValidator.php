<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\FileValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFileValidator extends FileValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value instanceof File && !$value instanceof PendingFile) {
            // we only validate PendingFile's and skip other files
            return;
        }

        if (null !== $value && '' !== $value && !$value instanceof PendingFile) {
            throw new UnexpectedTypeException($value, PendingFile::class);
        }

        parent::validate($value, $constraint);
    }
}

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
use Symfony\Component\Validator\Constraints\ImageValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class PendingImageValidator extends ImageValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value instanceof Image && !$value instanceof PendingImage) {
            // we only validate PendingImage's and skip other images
            return;
        }

        if (null !== $value && '' !== $value && !$value instanceof PendingImage) {
            throw new UnexpectedTypeException($value, PendingImage::class);
        }

        parent::validate($value, $constraint);
    }
}

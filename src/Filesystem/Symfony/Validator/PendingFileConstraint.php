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

use Symfony\Component\Validator\Constraints\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFileConstraint extends File
{
    public function validatedBy(): string
    {
        return PendingFileValidator::class;
    }
}

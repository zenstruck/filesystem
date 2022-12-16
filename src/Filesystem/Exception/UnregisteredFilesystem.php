<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnregisteredFilesystem extends \RuntimeException
{
    public function __construct(string $name, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Filesystem "%s" is not registered.', $name), previous: $previous);
    }
}

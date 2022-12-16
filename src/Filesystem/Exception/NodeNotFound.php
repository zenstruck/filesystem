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

use League\Flysystem\FilesystemException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNotFound extends \RuntimeException implements FilesystemException
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Node at path "%s" not found.', $path), previous: $previous);
    }
}

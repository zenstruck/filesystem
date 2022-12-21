<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File\Path;

use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackPathGenerator implements Generator
{
    /** @var callable(File,array):string */
    private $callback;

    /**
     * @param callable(File,array):string $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function generatePath(File $file, array $context = []): string
    {
        return ($this->callback)($file, $context);
    }
}

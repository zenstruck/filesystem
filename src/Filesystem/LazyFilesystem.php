<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFilesystem implements Filesystem
{
    use DecoratedFilesystem;

    /** @var callable():Filesystem|Filesystem */
    private $filesystem;

    /**
     * @param callable():Filesystem $filesystem
     */
    public function __construct(callable $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    protected function inner(): Filesystem
    {
        if ($this->filesystem instanceof Filesystem) {
            return $this->filesystem;
        }

        return $this->filesystem = ($this->filesystem)();
    }
}

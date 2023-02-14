<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Archive;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\DecoratedFilesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\Node\Path;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TarFile extends \SplFileInfo implements Filesystem
{
    use DecoratedFilesystem;

    private FlysystemFilesystem $inner;

    public function __construct(string $filename)
    {
        if (!\in_array((new Path($filename))->extension(), ['tar', 'tar.gz', 'tar.bz2'])) {
            throw new \InvalidArgumentException(\sprintf('File "%s" must be a valid tar(.gz/bz2) file.', $filename));
        }

        if (!\is_file($filename) || !\is_readable($filename)) {
            throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $filename));
        }

        parent::__construct($filename);
    }

    protected function inner(): Filesystem
    {
        return $this->inner ??= new FlysystemFilesystem('readonly:phar://'.$this, 'phar://'.$this);
    }
}

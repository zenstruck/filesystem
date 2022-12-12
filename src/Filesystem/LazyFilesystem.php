<?php

namespace Zenstruck\Filesystem;

use Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyFilesystem extends DecoratedFilesystem
{
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

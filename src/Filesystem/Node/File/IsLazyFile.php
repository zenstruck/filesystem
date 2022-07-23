<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Flysystem\Operator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsLazyFile
{
    private Filesystem $filesystem;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }

    protected function operator(): Operator
    {
        if (isset($this->operator)) {
            return $this->operator;
        }

        if (!isset($this->filesystem)) {
            throw new \LogicException('The filesystem has not been set.');
        }

        return $this->operator = $this->filesystem->file($this->path)->operator();
    }
}

<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\Operator;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
trait IsLazyFile
{
    private Filesystem $filesystem;

    public function __construct(string $path, ?Filesystem $filesystem = null)
    {
        $this->path = $path;

        if ($filesystem) {
            $this->filesystem = $filesystem;
        }
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

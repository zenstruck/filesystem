<?php

namespace Zenstruck\Filesystem\Node\File;

use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of File
 *
 * @extends FileCollection<T>
 *
 * @internal
 */
final class LazyFileCollection extends FileCollection
{
    private bool $initialized = false;
    private Filesystem $filesystem;

    /**
     * @return T[]
     */
    public function all(): array
    {
        if (!isset($this->filesystem) || $this->initialized) {
            return parent::all();
        }

        $files = parent::all();

        foreach ($files as $file) {
            if ($file instanceof LazyNode) {
                $file->setFilesystem($this->filesystem);
            }
        }

        $this->initialized = true;

        return $files;
    }

    public function setFilesystem(Filesystem $filesystem): void
    {
        $this->filesystem = $filesystem;
    }
}

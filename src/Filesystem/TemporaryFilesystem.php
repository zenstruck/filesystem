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

use League\Flysystem\FilesystemException;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\TemporaryImage;
use Zenstruck\Filesystem\Node\File\TemporaryFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class TemporaryFilesystem implements Filesystem
{
    use DecoratedFilesystem;

    public function __construct(private Filesystem $inner)
    {
    }

    public function node(string $path): Node
    {
        $node = $this->inner()->node($path);

        if ($node instanceof File) {
            return $this->ensureTemporary($node);
        }

        return $node;
    }

    public function file(string $path): File
    {
        return $this->ensureTemporary(
            $this->inner()->file($path)
        );
    }

    public function image(string $path): Image
    {
        $image = $this->ensureTemporary(
            $this->inner()->image($path)
        );
        \assert($image instanceof TemporaryImage);

        return $image;
    }

    public function copy(string $source, string $destination, array $config = []): File
    {
        return $this->ensureTemporary(
            $this->inner()->copy($source, $destination, $config)
        );
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        return $this->ensureTemporary(
            $this->inner()->move($source, $destination, $config)
        );
    }

    public function chmod(string $path, string $visibility): Node
    {
        return $this->ensureTemporary(
            $this->inner()->chmod($path, $visibility)
        );
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        return $this->ensureTemporary(
            $this->inner()->write($path, $value, $config)
        );
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }

    /**
     * @throws FilesystemException
     */
    private function ensureTemporary(Node $node): TemporaryFile
    {
        if ($node instanceof TemporaryFile) {
            return $node;
        }

        if ($node instanceof Image) {
            return new TemporaryImage($node);
        }

        return new TemporaryFile($node->ensureFile());
    }
}

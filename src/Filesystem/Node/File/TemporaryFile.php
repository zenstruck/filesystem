<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node\File;

use League\Flysystem\FilesystemException;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\DecoratedNode;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image\TemporaryImage;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class TemporaryFile implements File
{
    use DecoratedFile, DecoratedNode;

    public function __construct(private File $file)
    {
    }

    public function ensureFile(): File
    {
        return $this->ensureTemporary(
            $this->inner()->ensureFile()
        );
    }

    public function ensureImage(): Image
    {
        $image = $this->ensureTemporary(
            $this->inner()->ensureImage()
        );
        assert($image instanceof TemporaryImage);

        return $image;
    }

    protected function inner(): File
    {
        return $this->file;
    }

    /**
     * @throws FilesystemException
     */
    private function ensureTemporary(Node $node): TemporaryFile
    {
        if ($node instanceof self) {
            return $node;
        }

        if ($node instanceof Image) {
            return new TemporaryImage($node);
        }

        return new TemporaryFile($node->ensureFile());
    }
}

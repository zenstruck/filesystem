<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node\File\DecoratedFile;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\DecoratedImage;
use Zenstruck\Image\LocalImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestImage extends TestNode implements Image
{
    use DecoratedFile, DecoratedImage;

    public function __construct(private Image $inner)
    {
    }

    public function assertHeight(int $expected): self
    {
        Assert::that($this->height())->is($expected, 'Expected height to be {expected} but is actually {actual}.');

        return $this;
    }

    public function assertWidth(int $expected): self
    {
        Assert::that($this->width())->is($expected, 'Expected width to be {expected} but is actually {actual}.');

        return $this;
    }

    public function tempFile(): LocalImage
    {
        return new LocalImage($this->inner->tempFile());
    }

    protected function inner(): Image
    {
        return $this->inner;
    }
}

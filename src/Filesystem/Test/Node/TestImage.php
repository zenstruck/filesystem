<?php

namespace Zenstruck\Filesystem\Test\Node;

use Zenstruck\Assert;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\WrappedImage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TestImage implements Image
{
    use IsTestFile, WrappedImage {
        IsTestFile::ensureFile insteadof WrappedImage;
        IsTestFile::ensureImage insteadof WrappedImage;
        IsTestFile::ensureDirectory insteadof WrappedImage;
        IsTestFile::directory insteadof WrappedImage;
    }

    public function __construct(private Image $image)
    {
    }

    /**
     * @param int|callable(int):void $expected
     */
    public function assertHeight(int|callable $expected): self
    {
        if (\is_int($expected)) {
            Assert::that($this->height())->is($expected, 'Expected height to be {expected} but is actually {actual}.');

            return $this;
        }

        ($expected)($this->height());

        return $this;
    }

    /**
     * @param int|callable(int):void $expected
     */
    public function assertWidth(int|callable $expected): self
    {
        if (\is_int($expected)) {
            Assert::that($this->width())->is($expected, 'Expected width to be {expected} but is actually {actual}.');

            return $this;
        }

        ($expected)($this->width());

        return $this;
    }

    protected function inner(): Image
    {
        return $this->image;
    }
}

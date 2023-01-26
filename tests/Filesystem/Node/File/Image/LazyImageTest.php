<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File\Image;

use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Tests\Filesystem\Node\File\ImageTests;
use Zenstruck\Tests\Filesystem\Node\File\LazyFileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyImageTest extends LazyFileTest
{
    use ImageTests;

    /**
     * @test
     */
    public function can_create_with_image_attributes(): void
    {
        $file = $this->createLazyFile([
            'transform_url' => [
                'filter1' => '/filter1',
                'filter2' => '/filter2',
            ],
            'dimensions' => [48, 22],
            'exif' => [
                'foo' => 'bar',
            ],
            'iptc' => [
                'baz' => 'foo',
            ],
        ]);

        $this->assertSame('/filter1', $file->transformUrl('filter1'));
        $this->assertSame('/filter2', $file->transformUrl('filter2'));
        $this->assertSame(22, $file->dimensions()->height());
        $this->assertSame(48, $file->dimensions()->width());
        $this->assertFalse($file->dimensions()->isPortrait());
        $this->assertFalse($file->dimensions()->isSquare());
        $this->assertTrue($file->dimensions()->isLandscape());
        $this->assertSame(1056, $file->dimensions()->pixels());
        $this->assertSame(2.18, \round($file->dimensions()->aspectRatio(), 2));
        $this->assertSame(['foo' => 'bar'], $file->exif());
        $this->assertSame(['baz' => 'foo'], $file->iptc());
    }

    /**
     * @test
     */
    public function can_create_with_image_dimensions_as_assoc_array(): void
    {
        $file = $this->createLazyFile([
            'dimensions' => [
                'width' => 48,
                'height' => 22,
            ],
        ]);

        $this->assertSame(22, $file->dimensions()->height());
        $this->assertSame(48, $file->dimensions()->width());
    }

    /**
     * @test
     */
    public function can_create_with_image_dimensions_as_assoc_array_reversed(): void
    {
        $file = $this->createLazyFile([
            'dimensions' => [
                'height' => 22,
                'width' => 48,
            ],
        ]);

        $this->assertSame(22, $file->dimensions()->height());
        $this->assertSame(48, $file->dimensions()->width());
    }

    protected function createLazyFile(string|callable|array|null $attributes = null): LazyImage
    {
        return new LazyImage($attributes);
    }

    protected function createFile(\SplFileInfo $file, string $path): Image
    {
        $image = new LazyImage($path);
        $image->setFilesystem($this->filesystem->write($path, $file));

        return $image;
    }
}

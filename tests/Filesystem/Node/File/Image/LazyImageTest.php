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
            'transformUrl' => [
                'filter1' => '/filter1',
                'filter2' => '/filter2',
            ],
            'height' => 22,
            'width' => 48,
            'exif' => [
                'foo' => 'bar',
            ],
            'iptc' => [
                'baz' => 'foo',
            ],
        ]);

        $this->assertSame('/filter1', $file->transformUrl('filter1'));
        $this->assertSame('/filter2', $file->transformUrl('filter2'));
        $this->assertSame(22, $file->height());
        $this->assertSame(48, $file->width());
        $this->assertFalse($file->isPortrait());
        $this->assertFalse($file->isSquare());
        $this->assertTrue($file->isLandscape());
        $this->assertSame(1056, $file->pixels());
        $this->assertSame(2.18, \round($file->aspectRatio(), 2));
        $this->assertSame(['foo' => 'bar'], $file->exif());
        $this->assertSame(['baz' => 'foo'], $file->iptc());
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

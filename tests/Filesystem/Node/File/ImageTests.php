<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File;

use Intervention\Image\Image as InterventionImage;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ImageTests
{
    /**
     * @test
     */
    public function can_get_image_metadata(): void
    {
        $image = $this->createFile(fixture('symfony.png'), 'symfony.png');

        $this->assertSame(678, $image->dimensions()->height());
        $this->assertSame(563, $image->dimensions()->width());
        $this->assertSame($image->dimensions()->width() * $image->dimensions()->height(), $image->dimensions()->pixels());
        $this->assertSame($image->dimensions()->width() / $image->dimensions()->height(), $image->dimensions()->aspectRatio());
        $this->assertSame($image->dimensions()->height() === $image->dimensions()->width(), $image->dimensions()->isSquare());
        $this->assertSame($image->dimensions()->height() < $image->dimensions()->width(), $image->dimensions()->isLandscape());
        $this->assertSame($image->dimensions()->height() > $image->dimensions()->width(), $image->dimensions()->isPortrait());
    }

    /**
     * @test
     */
    public function can_get_exif_and_iptc_data(): void
    {
        $image = $this->createFile(fixture('metadata.jpg'), 'symfony.png');

        $this->assertSame(16, $image->exif()['computed.Height']);
        $this->assertSame('Lorem Ipsum', $image->iptc()['DocumentTitle']);
    }

    /**
     * @test
     */
    public function can_transform_image(): void
    {
        $image = $this->createFile(fixture('symfony.png'), 'symfony.png');

        $transformed = $image->transform(fn(InterventionImage $image) => $image->widen(100));

        $this->assertSame(100, $transformed->dimensions()->width());
        $this->assertSame(563, $image->refresh()->dimensions()->width());
    }

    /**
     * @test
     */
    public function can_get_transform_url(): void
    {
        $image = $this->createFile(fixture('symfony.png'), 'path/symfony.png');

        $this->assertSame('/generate/path/symfony.png?filter=some-filter', $image->transformUrl('some-filter'));
    }

    abstract protected function createFile(\SplFileInfo $file, string $path): Image;
}

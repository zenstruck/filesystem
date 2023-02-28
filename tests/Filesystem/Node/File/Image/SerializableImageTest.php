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

use Zenstruck\Filesystem\Node\File\Image\SerializableImage;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Tests\Filesystem\Node\File\ImageTests;
use Zenstruck\Tests\Filesystem\Node\File\SerializableFileTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SerializableImageTest extends SerializableFileTest
{
    use ImageTests;

    /**
     * @test
     */
    public function serialize_metadata(): void
    {
        $serialized = $this->createFile(fixture('metadata.jpg'), 'some/image.jpg', [
            Mapping::EXIF,
            Mapping::IPTC,
        ])->serialize();

        $this->assertSame(16, $serialized['exif']['computed.Height']);
        $this->assertSame('Lorem Ipsum', $serialized['iptc']['DocumentTitle']);
    }

    protected function serializedProvider(): iterable
    {
        yield from parent::serializedProvider();

        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Mapping::DIMENSIONS,
            ]),
            [
                'dimensions' => [
                    'width' => 563,
                    'height' => 678,
                ],
            ],
        ];

        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Mapping::TRANSFORM_URL => 'grayscale',
            ]),
            [
                'transform_url' => [
                    'grayscale' => '/generate/some/image.jpg?filter=grayscale',
                ],
            ],
        ];

        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Mapping::TRANSFORM_URL => ['grayscale', 'thumbnail'],
            ]),
            [
                'transform_url' => [
                    'grayscale' => '/generate/some/image.jpg?filter=grayscale',
                    'thumbnail' => '/generate/some/image.jpg?filter=thumbnail',
                ],
            ],
        ];
    }

    protected function createFile(\SplFileInfo $file, string $path, string|array $metadata = []): SerializableImage
    {
        return new SerializableImage($this->filesystem->write($path, $file)->ensureImage(), $metadata);
    }
}

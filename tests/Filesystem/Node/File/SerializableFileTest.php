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

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File\SerializableFile;
use Zenstruck\Filesystem\Node\Metadata;
use Zenstruck\Tests\Filesystem\Node\FileTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class SerializableFileTest extends TestCase
{
    use FileTests;

    /**
     * @test
     */
    public function can_serialize(): void
    {
        foreach ($this->serializedProvider() as [$file, $expected]) {
            $this->assertSame($expected, $file->jsonSerialize());
        }
    }

    /**
     * @test
     */
    public function serialize_last_modified(): void
    {
        $file = $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
            Metadata::LAST_MODIFIED,
        ]);

        $this->assertSame($file->serialize()[Metadata::LAST_MODIFIED], $file->lastModified()->format('c'));
    }

    protected function serializedProvider(): iterable
    {
        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Metadata::PATH,
                Metadata::DSN,
                Metadata::VISIBILITY,
                Metadata::MIME_TYPE,
                Metadata::CHECKSUM,
                Metadata::SIZE,
                Metadata::PUBLIC_URL,
            ]),
            [
                'path' => 'some/image.jpg',
                'dsn' => 'default://some/image.jpg',
                'visibility' => 'public',
                'mime_type' => 'image/jpeg',
                'checksum' => '42890a25562a1803949caa09d235f242',
                'size' => 25884,
                'public_url' => '/prefix/some/image.jpg',
            ],
        ];

        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Metadata::PATH,
                Metadata::CHECKSUM => 'sha1',
            ]),
            [
                'path' => 'some/image.jpg',
                'checksum' => [
                    'sha1' => '4dadf4a29cdc3b57ab8564f5651b30e236ca536d',
                ],
            ],
        ];

        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Metadata::PATH,
                Metadata::CHECKSUM => ['sha1', 'md5'],
            ]),
            [
                'path' => 'some/image.jpg',
                'checksum' => [
                    'sha1' => '4dadf4a29cdc3b57ab8564f5651b30e236ca536d',
                    'md5' => '42890a25562a1803949caa09d235f242',
                ],
            ],
        ];

        yield [$this->createFile(fixture('symfony.jpg'), 'some/image.jpg', Metadata::PATH), 'some/image.jpg'];
        yield [$this->createFile(fixture('symfony.jpg'), 'some/image.jpg', Metadata::DSN), 'default://some/image.jpg'];
    }

    protected function createFile(\SplFileInfo $file, string $path, array|string $metadata = []): SerializableFile
    {
        return new SerializableFile($this->filesystem->write($path, $file)->last()->ensureFile(), $metadata);
    }
}

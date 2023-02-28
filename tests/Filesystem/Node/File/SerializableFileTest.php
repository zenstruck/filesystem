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
use Zenstruck\Filesystem\Node\Mapping;
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
            Mapping::LAST_MODIFIED,
        ]);

        $this->assertSame($file->serialize()[Mapping::LAST_MODIFIED], $file->lastModified()->format('c'));
    }

    protected function serializedProvider(): iterable
    {
        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Mapping::PATH,
                Mapping::DSN,
                Mapping::VISIBILITY,
                Mapping::MIME_TYPE,
                Mapping::CHECKSUM,
                Mapping::SIZE,
                Mapping::PUBLIC_URL,
                Mapping::EXTENSION,
            ]),
            [
                'path' => 'some/image.jpg',
                'dsn' => 'default://some/image.jpg',
                'visibility' => 'public',
                'mime_type' => 'image/jpeg',
                'checksum' => '42890a25562a1803949caa09d235f242',
                'size' => 25884,
                'public_url' => '/prefix/some/image.jpg',
                'extension' => 'jpg',
            ],
        ];

        yield [
            $this->createFile(fixture('symfony.jpg'), 'some/image.jpg', [
                Mapping::PATH,
                Mapping::CHECKSUM => 'sha1',
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
                Mapping::PATH,
                Mapping::CHECKSUM => ['sha1', 'md5'],
            ]),
            [
                'path' => 'some/image.jpg',
                'checksum' => [
                    'sha1' => '4dadf4a29cdc3b57ab8564f5651b30e236ca536d',
                    'md5' => '42890a25562a1803949caa09d235f242',
                ],
            ],
        ];

        yield [$this->createFile(fixture('symfony.jpg'), 'some/image.jpg', Mapping::PATH), 'some/image.jpg'];
        yield [$this->createFile(fixture('symfony.jpg'), 'some/image.jpg', Mapping::DSN), 'default://some/image.jpg'];
    }

    protected function createFile(\SplFileInfo $file, string $path, array|string $metadata = []): SerializableFile
    {
        return new SerializableFile($this->filesystem->write($path, $file), $metadata);
    }
}

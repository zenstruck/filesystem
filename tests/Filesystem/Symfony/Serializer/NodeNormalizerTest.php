<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Serializer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\Mapping;
use Zenstruck\Filesystem\Node\Metadata;
use Zenstruck\Filesystem\Node\Path\Expression;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNormalizerTest extends KernelTestCase
{
    use InteractsWithFilesystem;

    /**
     * @test
     * @dataProvider normalizeProvider
     *
     * @param callable(Filesystem):Node $factory
     */
    public function can_normalize_and_denormalize(callable $factory, array $context, string $type, array|string $expectedJson): void
    {
        $node = $factory($this->filesystem());
        $serialized = $this->serializer()->serialize($node, 'json', $context);

        $this->assertSame($expectedJson, \json_decode($serialized, true));

        $deserialized = $this->serializer()->deserialize($serialized, $type, 'json', $context);

        $this->assertInstanceOf($type, $deserialized);
        $this->assertTrue($deserialized->exists());
        $this->assertSame($node->path()->toString(), $deserialized->path()->toString());
        $this->assertEquals($node->lastModified(), $deserialized->lastModified());
    }

    public static function normalizeProvider(): iterable
    {
        yield [
            fn(Filesystem $f) => $f->write('some/file.txt', 'content'),
            [],
            File::class,
            'public://some/file.txt',
        ];
        yield [
            fn(Filesystem $f) => $f->mkdir('some/dir')->ensureDirectory(),
            [],
            Directory::class,
            'public://some/dir',
        ];
        yield [
            fn(Filesystem $f) => $f->write('some/file.txt', 'content'),
            ['filesystem' => 'public'],
            File::class,
            'some/file.txt',
        ];
        yield [
            fn(Filesystem $f) => $f->mkdir('some/dir')->ensureDirectory(),
            ['filesystem' => 'public'],
            Directory::class,
            'some/dir',
        ];
        yield [
            fn(Filesystem $f) => $f->write('some/file.txt', 'content'),
            ['filesystem' => 'public', 'metadata' => [Metadata::PATH, Metadata::CHECKSUM, Metadata::SIZE]],
            File::class,
            [
                'path' => 'some/file.txt',
                'checksum' => '9a0364b9e99bb480dd25e1f0284c8555',
                'size' => 7,
            ],
        ];
        yield [
            fn(Filesystem $f) => $f->write('some/file.txt', 'content'),
            ['filesystem' => new Mapping([Metadata::PATH, Metadata::CHECKSUM, Metadata::SIZE], 'public')],
            File::class,
            [
                'path' => 'some/file.txt',
                'checksum' => '9a0364b9e99bb480dd25e1f0284c8555',
                'size' => 7,
            ],
        ];
        yield [
            fn(Filesystem $f) => $f->write('some/image.png', fixture('symfony.png'))->ensureImage(),
            ['metadata' => [Metadata::DSN, Metadata::SIZE, Metadata::DIMENSIONS]],
            Image::class,
            [
                'dsn' => 'public://some/image.png',
                'size' => 10862,
                'dimensions' => [
                    'width' => 563,
                    'height' => 678,
                ],
            ],
        ];
        yield [
            fn(Filesystem $f) => $f->write('9a0364b9e99bb480dd25e1f0284c8555.txt', 'content'),
            [
                'filesystem' => 'public',
                'metadata' => [Metadata::CHECKSUM, Metadata::SIZE, Metadata::EXTENSION],
                'namer' => Expression::checksum(),
            ],
            File::class,
            [
                'checksum' => '9a0364b9e99bb480dd25e1f0284c8555',
                'size' => 7,
                'extension' => 'txt',
            ],
        ];
        yield [
            fn(Filesystem $f) => $f->write('some/prefix/some-file.txt', 'content'),
            [
                'filesystem' => 'public',
                'metadata' => [Metadata::CHECKSUM, Metadata::SIZE, Metadata::FILENAME],
                'namer' => new Expression('some/prefix/{name}{ext}'),
            ],
            File::class,
            [
                'checksum' => '9a0364b9e99bb480dd25e1f0284c8555',
                'size' => 7,
                'filename' => 'some-file.txt',
            ],
        ];
        yield [
            fn(Filesystem $f) => $f->write('some/prefix/some-file.txt', 'content'),
            [
                'filesystem' => 'public',
                'metadata' => Metadata::FILENAME,
                'namer' => new Expression('some/prefix/{name}{ext}'),
            ],
            File::class,
            'some-file.txt',
        ];
    }

    private function serializer(): SerializerInterface
    {
        return self::getContainer()->get('serializer');
    }
}

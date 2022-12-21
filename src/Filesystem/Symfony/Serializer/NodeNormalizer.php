<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Serializer;

use Psr\Container\ContainerInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\Directory\LazyDirectory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    public const FILESYSTEM_KEY = 'filesystem';

    private const TYPE_MAP = [
        File::class => LazyFile::class,
        Directory::class => LazyDirectory::class,
        Image::class => LazyImage::class,
    ];

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * @param Node $object
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        return isset($context[self::FILESYSTEM_KEY]) ? $object->path() : $object->dsn();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Node && !$data instanceof PendingFile;
    }

    /**
     * @param string $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Node
    {
        if (!\is_string($data)) {
            throw new UnexpectedValueException('Data must be a string.');
        }

        $filesystem = null;
        $path = $data;

        if (2 === \count($parts = \explode('://', $data, 2))) {
            [$filesystem, $path] = $parts;
        }

        if (isset($context[self::FILESYSTEM_KEY])) {
            $filesystem = $context[self::FILESYSTEM_KEY]; // context always takes priority
        }

        /** @var LazyNode $node */
        $node = new (self::TYPE_MAP[$type])($path);

        if ($filesystem) {
            $node->setFilesystem(fn() => $this->container->get($filesystem));
        }

        return $node;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return isset(self::TYPE_MAP[$type]);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Flysystem;

use League\Flysystem\Config;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Operator implements FilesystemOperator
{
    public function __construct(
        private FilesystemOperator $inner,
        private string $name,
        private array|ContainerInterface $features,
    ) {
    }

    public function transformUrl(string $path, array|string $filter, array $config = []): string
    {
        return $this->feature(TransformUrlGenerator::class)->transformUrl($path, $filter, new Config($config));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function publicUrl(string $path, array $config = []): string
    {
        try {
            return $this->feature(PublicUrlGenerator::class)->publicUrl($path, new Config($config));
        } catch (UnsupportedFeature) {
            return $this->inner->publicUrl($path, $config);
        }
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiresAt, array $config = []): string
    {
        try {
            return $this->feature(TemporaryUrlGenerator::class)->temporaryUrl($path, $expiresAt, new Config($config));
        } catch (UnsupportedFeature) {
            return $this->inner->temporaryUrl($path, $expiresAt, $config);
        }
    }

    public function checksum(string $path, array $config = []): string
    {
        return $this->inner->checksum($path, $config);
    }

    public function fileExists(string $location): bool
    {
        return $this->inner->fileExists($location);
    }

    public function directoryExists(string $location): bool
    {
        return $this->inner->directoryExists($location);
    }

    public function has(string $location): bool
    {
        return $this->inner->has($location);
    }

    public function read(string $location): string
    {
        return $this->inner->read($location);
    }

    public function readStream(string $location)
    {
        return $this->inner->readStream($location);
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        return $this->inner->listContents($location, $deep);
    }

    public function lastModified(string $path): int
    {
        return $this->inner->lastModified($path);
    }

    public function fileSize(string $path): int
    {
        return $this->inner->fileSize($path);
    }

    public function mimeType(string $path): string
    {
        return $this->inner->mimeType($path);
    }

    public function visibility(string $path): string
    {
        return $this->inner->visibility($path);
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        $this->inner->write($location, $contents, $config);
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        $this->inner->writeStream($location, $contents, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->inner->setVisibility($path, $visibility);
    }

    public function delete(string $location): void
    {
        $this->inner->delete($location);
    }

    public function deleteDirectory(string $location): void
    {
        $this->inner->deleteDirectory($location);
    }

    public function createDirectory(string $location, array $config = []): void
    {
        $this->inner->createDirectory($location, $config);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        $this->inner->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        $this->inner->copy($source, $destination, $config);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $feature
     *
     * @return T
     *
     * @throws UnsupportedFeature
     */
    private function feature(string $feature): object
    {
        if ($this->features instanceof ContainerInterface) {
            try {
                return $this->features->get($feature);
            } catch (NotFoundExceptionInterface $e) {
            }
        }

        if (\is_array($this->features) && isset($this->features[$feature])) {
            return $this->features[$feature];
        }

        throw new UnsupportedFeature($feature, $this->name(), $e ?? null);
    }
}

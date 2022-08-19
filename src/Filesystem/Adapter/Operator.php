<?php

namespace Zenstruck\Filesystem\Adapter;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;
use Psr\Container\ContainerInterface;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\FileChecksum;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Feature\TransformImageUrl;
use Zenstruck\Filesystem\Feature\ModifyFile;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\TempFile;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type GlobalConfig from AdapterFilesystem
 * @phpstan-import-type Features from AdapterFilesystem
 */
final class Operator extends Filesystem implements FileChecksum, ModifyFile, FileUrl, TransformImageUrl
{
    private PathNormalizer $normalizer;

    /**
     * @param GlobalConfig|array<string,mixed> $config
     * @param Features                         $features
     */
    public function __construct(private FilesystemAdapter $adapter, private array $config = [], private iterable|ContainerInterface $features = [])
    {
        parent::__construct($adapter, $config, $this->normalizer = $config['path_normalizer'] ?? new WhitespacePathNormalizer());
    }

    public function fileAttributesFor(string $path): FileAttributes
    {
        return new FileAttributes($this->normalizer->normalizePath($path));
    }

    public function directoryAttributesFor(string $path): DirectoryAttributes
    {
        return new DirectoryAttributes($this->normalizer->normalizePath($path));
    }

    public function md5ChecksumFor(File $file): string
    {
        if ($feature = $this->feature(FileChecksum::class)) {
            return $feature->md5ChecksumFor($file);
        }

        return \md5($file->contents());
    }

    public function sha1ChecksumFor(File $file): string
    {
        if ($feature = $this->feature(FileChecksum::class)) {
            return $feature->sha1ChecksumFor($file);
        }

        return \sha1($file->contents());
    }

    public function urlFor(File $file, mixed $options = []): Uri
    {
        if (!$feature = $this->feature(FileUrl::class)) {
            throw new UnsupportedFeature(\sprintf('"%s" is not supported.', FileUrl::class));
        }

        return $feature->urlFor($file, $options);
    }

    public function transformUrlFor(Image $image, mixed $options = []): Uri
    {
        if (!$feature = $this->feature(TransformImageUrl::class)) {
            throw new UnsupportedFeature(\sprintf('"%s" is not supported.', TransformImageUrl::class));
        }

        return $feature->transformUrlFor($image, $options);
    }

    public function realFile(File $file): \SplFileInfo
    {
        if ($feature = $this->feature(ModifyFile::class)) {
            return $feature->realFile($file);
        }

        return TempFile::for($file);
    }

    public function swap(FilesystemAdapter $adapter): void
    {
        parent::__construct($this->adapter = $adapter, $this->config, $this->normalizer);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $name
     *
     * @return ?T
     */
    private function feature(string $name): ?object
    {
        if ($this->adapter instanceof $name) {
            return $this->adapter;
        }

        if ($this->features instanceof ContainerInterface) {
            return $this->features->has($name) ? $this->features->get($name) : null;
        }

        foreach ($this->features as $feature) {
            if ($feature instanceof $name) {
                return $feature;
            }
        }

        return null;
    }
}

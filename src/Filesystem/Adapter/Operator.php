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
use Zenstruck\Filesystem\Feature\DefaultSet;
use Zenstruck\Filesystem\Feature\FileChecksum;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Feature\ImageTransformer;
use Zenstruck\Filesystem\Feature\ModifyFile;
use Zenstruck\Filesystem\Feature\TransformImageUrl;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Util\TempFile;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @implements ImageTransformer<object>
 *
 * @phpstan-import-type GlobalConfig from AdapterFilesystem
 * @phpstan-import-type Features from AdapterFilesystem
 * @phpstan-import-type TransformOptions from ImageTransformer
 */
final class Operator extends Filesystem implements FileChecksum, ModifyFile, FileUrl, ImageTransformer, TransformImageUrl
{
    private PathNormalizer $normalizer;

    /**
     * @param GlobalConfig|array<string,mixed> $config
     * @param Features                         $features
     */
    public function __construct(private FilesystemAdapter $adapter, private string $name, private array $config = [], private iterable|ContainerInterface $features = [])
    {
        parent::__construct($adapter, $config, $this->normalizer = $config['path_normalizer'] ?? new WhitespacePathNormalizer());
    }

    public function context(string $path): string
    {
        return "{$this->name}://{$path}";
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
        return $this->featureOrFail(FileUrl::class)->urlFor($file, $options);
    }

    public function transformUrlFor(Image $image, mixed $options = []): Uri
    {
        return $this->featureOrFail(TransformImageUrl::class)->transformUrlFor($image, $options);
    }

    public function realFile(File $file): \SplFileInfo
    {
        if ($feature = $this->feature(ModifyFile::class)) {
            return $feature->realFile($file);
        }

        return TempFile::for($file);
    }

    /**
     * @param TransformOptions $options
     */
    public function transform(Image $image, callable $manipulator, array $options): \SplFileInfo
    {
        return $this->featureOrFail(ImageTransformer::class)->transform($image, $manipulator, $options);
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

        if ($this->features instanceof ContainerInterface && $this->features->has($name)) {
            return $this->features->get($name);
        }

        if (\is_iterable($this->features)) {
            foreach ($this->features as $feature) {
                if ($feature instanceof $name) {
                    return $feature;
                }
            }
        }

        return DefaultSet::get($name);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $name
     *
     * @return T
     */
    private function featureOrFail(string $name): object
    {
        return $this->feature($name) ?? throw new UnsupportedFeature(\sprintf('Feature "%s" is not supported by filesystem "%s".', $name, $this->name));
    }
}

<?php

namespace Zenstruck\Filesystem\Adapter;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;
use Zenstruck\Filesystem\AdapterFilesystem;
use Zenstruck\Filesystem\Feature\All;
use Zenstruck\Filesystem\Feature\FileChecksum;
use Zenstruck\Filesystem\Feature\ModifyFile;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\TempFile;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type GlobalConfig from AdapterFilesystem
 */
final class Operator extends Filesystem implements All
{
    private FeatureAwareAdapter $adapter;
    private PathNormalizer $normalizer;

    /**
     * @param GlobalConfig|array<string,mixed> $config
     */
    public function __construct(FilesystemAdapter $adapter, array $config = [])
    {
        if (!$adapter instanceof FeatureAwareAdapter) {
            $adapter = new FeatureAwareAdapter($adapter);
        }

        if ($prefixes = $config['url_prefix'] ?? $config['url_prefixes'] ?? null) {
            $adapter = new FileUrlPrefixAdapter($adapter, $prefixes);
        }

        parent::__construct($this->adapter = $adapter, $config, $this->normalizer = $config['path_normalizer'] ?? new WhitespacePathNormalizer());
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
        if ($this->adapter->supports(FileChecksum::class)) {
            return $this->adapter->md5ChecksumFor($file);
        }

        return \md5($file->contents());
    }

    public function sha1ChecksumFor(File $file): string
    {
        if ($this->adapter->supports(FileChecksum::class)) {
            return $this->adapter->sha1ChecksumFor($file);
        }

        return \sha1($file->contents());
    }

    public function urlFor(File $file, array $options = []): Uri
    {
        return $this->adapter->urlFor($file, $options);
    }

    public function realFile(File $file): \SplFileInfo
    {
        if ($this->adapter->supports(ModifyFile::class)) {
            return $this->adapter->realFile($file);
        }

        return TempFile::with($file->read());
    }

    public function swap(FilesystemAdapter $adapter): void
    {
        $this->adapter->swap($adapter);
    }
}

<?php

namespace Zenstruck\Filesystem\Flysystem;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;
use League\Flysystem\WhitespacePathNormalizer;
use Zenstruck\Filesystem\Feature\All;
use Zenstruck\Filesystem\Feature\FileChecksum;
use Zenstruck\Filesystem\Feature\ModifyFile;
use Zenstruck\Filesystem\Flysystem\Adapter\WrappedAdapter;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Operator extends Filesystem implements All
{
    private WrappedAdapter $adapter;
    private PathNormalizer $normalizer;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(FilesystemAdapter $adapter, array $config = [], ?PathNormalizer $pathNormalizer = null)
    {
        if (!$adapter instanceof WrappedAdapter) {
            $adapter = new WrappedAdapter($adapter);
        }

        parent::__construct($this->adapter = $adapter, $config, $this->normalizer = $pathNormalizer ?: new WhitespacePathNormalizer());
    }

    public function fileAttributesFor(string $path): FileAttributes
    {
        return new FileAttributes($this->normalizer->normalizePath($path));
    }

    public function directoryAttributesFor(string $path): DirectoryAttributes
    {
        return new DirectoryAttributes($this->normalizer->normalizePath($path));
    }

    public function supports(string $feature): bool
    {
        return $this->adapter->supports($feature);
    }

    public function md5Checksum(File $file): string
    {
        if ($this->adapter->supports(FileChecksum::class)) {
            return $this->adapter->md5Checksum($file);
        }

        return \md5($file->contents());
    }

    public function sha1Checksum(File $file): string
    {
        if ($this->adapter->supports(FileChecksum::class)) {
            return $this->adapter->sha1Checksum($file);
        }

        return \sha1($file->contents());
    }

    public function modifyFile(File $file, callable $callback): \SplFileInfo
    {
        if ($this->adapter->supports(ModifyFile::class)) {
            return $this->adapter->modifyFile($file, $callback);
        }

        $tempFile = $callback(TempFile::with($file->read()));

        if (!$tempFile instanceof \SplFileInfo || !$tempFile->isReadable() || $tempFile->isDir()) {
            throw new \LogicException('Readable SplFileInfo (file) must be returned from callback.');
        }

        return $tempFile;
    }
}

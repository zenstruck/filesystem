<?php

namespace Zenstruck\Filesystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\ZipArchive\ZipArchiveAdapter as FlysystemAdapter;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Zenstruck\Filesystem\Adapter\ZipArchive\TransactionalZipArchiveProvider;

/**
 * Similar to Flysystem's {@see \League\Flysystem\ZipArchive\ZipArchiveAdapter}
 * but with some customizations.
 *
 * - when deleting root, delete the file
 * - when checking existence of root, check if file exists
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ZipArchiveAdapter extends WrappedAdapter
{
    private FlysystemAdapter $inner;
    private TransactionalZipArchiveProvider $provider;

    public function __construct(private string $filename)
    {
        $this->inner = new FlysystemAdapter($this->provider = new TransactionalZipArchiveProvider($filename), '/');
    }

    public function provider(): TransactionalZipArchiveProvider
    {
        return $this->provider;
    }

    public function directoryExists(string $path): bool
    {
        if ('' === $path) {
            return \is_file($this->filename);
        }

        return parent::directoryExists($path);
    }

    public function deleteDirectory(string $path): void
    {
        if ('' === $path) {
            (new SymfonyFilesystem())->remove($this->filename);

            return;
        }

        parent::deleteDirectory($path);
    }

    protected function inner(): FilesystemAdapter
    {
        return $this->inner;
    }
}

<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Archive;

use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\UnableToOpenZipArchive;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TransactionalZipArchiveProvider extends FilesystemZipArchiveProvider
{
    private TransactionalZipArchive $archive;

    public function __construct(private string $filename, int $localDirectoryPermissions = 0700)
    {
        parent::__construct($filename, $localDirectoryPermissions);
    }

    public function createZipArchive(): \ZipArchive
    {
        return $this->archive ?? parent::createZipArchive();
    }

    public function beginTransaction(): void
    {
        if (isset($this->archive)) {
            throw new \LogicException(\sprintf('A transaction is already in progress for "%s".', $this->filename));
        }

        $archive = parent::createZipArchive(); // ensure its directory is created
        $archive->close();

        $archive = new TransactionalZipArchive();

        if (true !== $archive->open($this->filename, \ZipArchive::CREATE)) {
            throw UnableToOpenZipArchive::atLocation($this->filename, $archive->getStatusString() ?: '');
        }

        $this->archive = $archive;
    }

    public function commit(?callable $callback = null): void
    {
        if (!isset($this->archive)) {
            throw new \LogicException(\sprintf('A transaction has not yet been started for "%s".', $this->filename));
        }

        if (false === $this->archive->commit($callback)) {
            throw new \RuntimeException('Unable to commit archive.');
        }

        unset($this->archive);
    }
}

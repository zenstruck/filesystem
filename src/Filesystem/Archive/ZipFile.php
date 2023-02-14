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

use League\Flysystem\ZipArchive\ZipArchiveAdapter as BaseZipArchiveAdapter;
use League\Flysystem\ZipArchive\ZipArchiveProvider;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Archive\Zip\TransactionalZipArchiveProvider;
use Zenstruck\Filesystem\Archive\Zip\ZipArchiveAdapter;
use Zenstruck\Filesystem\DecoratedFilesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\TempFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZipFile extends \SplFileInfo implements Filesystem
{
    use DecoratedFilesystem;

    private FlysystemFilesystem $inner;
    private TransactionalZipArchiveProvider $provider;

    public function __construct(?string $filename = null)
    {
        if (!\interface_exists(ZipArchiveProvider::class)) {
            throw new \LogicException('league/flysystem-ziparchive is required (composer require league/flysystem-ziparchive).');
        }

        parent::__construct($filename ?? TempFile::new()->delete());
    }

    /**
     * @param array{
     *     commit_progress?: callable(float):void
     * } $config
     */
    public static function zip(Node|\SplFileInfo|string $what, ?string $filename = null, array $config = []): self
    {
        $filesystem = new self($filename);
        $what = \is_string($what) ? new \SplFileInfo($what) : $what;

        if (\file_exists($filesystem)) {
            throw new \RuntimeException(\sprintf('Unable to zip %s, destination filename (%s) already exists.', $what instanceof Node ? $what->path() : $what, $filename));
        }

        if ($what instanceof \SplFileInfo && !\file_exists($what)) {
            throw new \RuntimeException(\sprintf('Unable to zip %s, this file does not exist.', $what));
        }

        $path = match (true) {
            $what instanceof \SplFileInfo && !$what->isDir() => $what->getFilename(),
            $what instanceof File => $what->path()->name(),
            default => '',
        };

        $filesystem
            ->beginTransaction()
            ->write($path, $what, $config)
            ->commit($config['commit_progress'] ?? null)
        ;

        return $filesystem;
    }

    public function delete(string $path = '', array $config = []): static
    {
        if (!\in_array($path, ['/', ''], true)) {
            $this->inner()->delete($path, $config);

            return $this;
        }

        if (false === @\unlink($this)) {
            throw new \RuntimeException(\sprintf('Error deleting "%s".', $this));
        }

        return $this;
    }

    public function has(string $path = ''): bool
    {
        return $this->inner()->has($path);
    }

    /**
     * Subsequent write operations will be "queued".
     */
    public function beginTransaction(): self
    {
        $this->provider()->beginTransaction();

        return $this;
    }

    /**
     * @param callable(float):void|null $callback Progress callback that takes the percentage (float between 0.0 and 1.0)
     *                                            as the argument. Called a maximum of 100 times.
     */
    public function commit(?callable $callback = null): self
    {
        $this->provider()->commit($callback);

        return $this;
    }

    protected function inner(): Filesystem
    {
        return $this->inner ??= new FlysystemFilesystem(
            new ZipArchiveAdapter(
                new BaseZipArchiveAdapter($this->provider(), '/'),
                $this
            ),
            "zip://{$this}"
        );
    }

    protected function provider(): TransactionalZipArchiveProvider
    {
        return $this->provider ??= new TransactionalZipArchiveProvider($this);
    }
}

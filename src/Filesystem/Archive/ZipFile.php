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
use Zenstruck\Filesystem\Node\Directory;
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
     * @param File|Directory|\SplFileInfo|non-empty-array<array-key,File|\SplFileInfo> $what
     * @param string|null                                                              $filename The filename to save the zip as
     * @param array{
     *     commit_progress?: callable(float):void
     * } $config
     */
    public static function compress(File|Directory|\SplFileInfo|array $what, ?string $filename = null, array $config = []): self
    {
        $filesystem = new self($filename);

        if (\file_exists($filesystem)) {
            throw new \RuntimeException(\sprintf('Unable to zip "%s", destination filename (%s) already exists.', match (true) {
                $what instanceof Node => $what->path(), $what instanceof \SplFileInfo => $what, default => \get_debug_type($what),
            }, $filename));
        }

        if ($what instanceof \SplFileInfo && !\file_exists($what)) {
            throw new \RuntimeException(\sprintf('Unable to zip %s, this file does not exist.', $what));
        }

        $filesystem->beginTransaction();

        if ($what instanceof Directory || ($what instanceof \SplFileInfo && $what->isDir())) {
            $filesystem->mkdir('', $what, $config);

            return $filesystem->commit($config['commit_progress'] ?? null);
        }

        if (!\is_array($what)) {
            $what = [$what];
        }

        if (!$what) {
            throw new \InvalidArgumentException('Array of files is empty.');
        }

        foreach ($what as $key => $file) {
            $path = match (true) {
                \is_string($key) => $key,
                $file instanceof \SplFileInfo => $file->getFilename(),
                $file instanceof File => $file->path()->name(),
                default => throw new \InvalidArgumentException(\sprintf('File "%s" is invalid.', \get_debug_type($file))),
            };

            $filesystem->write(
                match (true) {
                    \is_string($key) => $key,
                    $file instanceof \SplFileInfo => $file->getFilename(),
                    $file instanceof File => $file->path()->name(),
                    default => throw new \InvalidArgumentException(\sprintf('File "%s" is invalid.', \get_debug_type($file))),
                },
                match (true) {
                    $file instanceof \SplFileInfo && $file->isFile(), $file instanceof File => $file,
                    default => throw new \InvalidArgumentException(\sprintf('File "%s" is invalid.', \get_debug_type($file))),
                },
                $config
            );
        }

        return $filesystem->commit($config['commit_progress'] ?? null);
    }

    public function delete(string $path = '', array $config = []): self
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

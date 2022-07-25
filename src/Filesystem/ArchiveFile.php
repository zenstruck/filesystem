<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\ZipArchive\ZipArchiveAdapter as FlysystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\ZipArchiveAdapter;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type GlobalConfig from AdapterFilesystem
 */
final class ArchiveFile extends \SplFileInfo implements Filesystem
{
    use WrappedFilesystem;

    private Filesystem $inner;
    private ZipArchiveAdapter $adapter;

    /**
     * @param GlobalConfig|array<string,mixed> $config
     */
    public function __construct(?string $filename = null, array $config = [])
    {
        if (!\class_exists(FlysystemAdapter::class)) {
            throw new \LogicException(\sprintf('league/flysystem-ziparchive is required to use %s as a filesystem. Install with "composer install (--dev) league/flysystem-ziparchive".', self::class));
        }

        if (!$filename) {
            $tempFile = new TempFile();
            $tempFile->delete();

            $filename = (string) $tempFile;
        }

        parent::__construct($filename);

        $this->inner = new AdapterFilesystem(
            $this->adapter = new ZipArchiveAdapter($filename),
            $config,
            'zip://'.$filename
        );
    }

    /**
     * Subsequent write operations will be "queued".
     */
    public function beginTransaction(): self
    {
        $this->adapter->provider()->beginTransaction();

        return $this;
    }

    /**
     * @param callable|null $callback Progress callback that takes the percentage (float between 0.0 and 1.0)
     *                                as the argument. Called a maximum of 100 times.
     */
    public function commit(?callable $callback = null): self
    {
        $this->adapter->provider()->commit($callback);

        return $this;
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }
}

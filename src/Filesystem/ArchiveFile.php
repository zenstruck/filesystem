<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\ZipArchive\ZipArchiveAdapter as FlysystemAdapter;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Adapter\ZipArchiveAdapter;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type GlobalConfig from AdapterFilesystem
 * @phpstan-type ZipConfig = array{
 *     write_progress:callable(File):void,
 *     commit_progress:callable(float):void,
 * }
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
     * @param GlobalConfig|ZipConfig|array<string,mixed> $config
     */
    public static function zip(Node|\SplFileInfo|string $what, ?string $filename = null, array $config = []): self
    {
        $filesystem = new self($filename, $config);
        $what = \is_string($what) ? new \SplFileInfo($what) : $what;

        if (\file_exists($filesystem)) {
            throw new \RuntimeException(\sprintf('Unable to zip %s, destination filename (%s) already exists.', $what, $filename));
        }

        if ($what instanceof \SplFileInfo && !\file_exists($what)) {
            throw new \RuntimeException(\sprintf('Unable to zip %s, this file does not exist.', $what));
        }

        if (isset($config['write_progress'])) {
            $config['progress'] = $config['write_progress'];
        }

        $path = match (true) {
            $what instanceof \SplFileInfo && !$what->isDir() => $what->getFilename(),
            $what instanceof File => $what->name(),
            default => Filesystem::ROOT,
        };

        $filesystem
            ->beginTransaction()
            ->write($path, $what, $config)
            ->commit($config['commit_progress'] ?? null)
        ;

        return $filesystem;
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
     * @param callable(float):void|null $callback Progress callback that takes the percentage (float between 0.0 and 1.0)
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

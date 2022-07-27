<?php

namespace Zenstruck\Filesystem;

use League\Flysystem\ZipArchive\ZipArchiveAdapter as FlysystemAdapter;
use Symfony\Component\Finder\Finder;
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
            throw new \LogicException(\sprintf('league/flysystem-ziparchive is required to use %s as a filesystem. Install with "composer require league/flysystem-ziparchive".', self::class));
        }

        if (!$filename) {
            $filename = (string) TempFile::new()->delete();
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

    public static function tar(Node|\SplFileInfo|string $what, ?string $filename = null): \SplFileInfo
    {
        $filename = $filename ?? TempFile::new()->delete().'.tar';

        self::createTar($what, $filename);

        return new \SplFileInfo($filename);
    }

    public static function tarGz(Node|\SplFileInfo|string $what, ?string $filename = null): \SplFileInfo
    {
        $filename = $filename ?? TempFile::new()->delete().'.tar.gz';

        if (!\str_ends_with($filename, '.tar.gz')) {
            throw new \LogicException(\sprintf('Filename (%s) must end with ".tar.gz".', $filename));
        }

        $tar = self::createTar($what, $tarFile = \mb_substr($filename, 0, -3));
        $tar->compress(\Phar::GZ);

        Util::fs()->remove($tarFile);

        return new \SplFileInfo($filename);
    }

    public static function tarBz2(Node|\SplFileInfo|string $what, ?string $filename = null): \SplFileInfo
    {
        $filename = $filename ?? TempFile::new()->delete().'.tar.bz2';

        if (!\str_ends_with($filename, '.tar.bz2')) {
            throw new \LogicException(\sprintf('Filename (%s) must end with ".tar.bz2".', $filename));
        }

        $tar = self::createTar($what, $tarFile = \mb_substr($filename, 0, -4));
        $tar->compress(\Phar::BZ2);

        Util::fs()->remove($tarFile);

        return new \SplFileInfo($filename);
    }

    public function directory(string $path = Filesystem::ROOT): Directory
    {
        return $this->inner()->directory($path);
    }

    public function has(string $path = Filesystem::ROOT): bool
    {
        return $this->inner()->has($path);
    }

    public function delete(Directory|string $path = Filesystem::ROOT, array $config = []): static
    {
        $this->inner()->delete($path, $config);

        return $this;
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
     *                                            as the argument. Called a maximum of 100 times.
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

    private static function createTar(Node|\SplFileInfo|string $what, string $filename): \PharData
    {
        $what = \is_string($what) ? new \SplFileInfo($what) : $what;

        if (!\str_ends_with($filename, '.tar')) {
            throw new \LogicException(\sprintf('Filename (%s) must end with ".tar".', $filename));
        }

        if (!\is_dir(\dirname($filename))) {
            Util::fs()->mkdir(\dirname($filename));
        }

        if (\file_exists($filename)) {
            throw new \RuntimeException(\sprintf('Unable to tar %s, destination filename (%s) already exists.', $what, $filename));
        }

        if ($what instanceof \SplFileInfo && !\file_exists($what)) {
            throw new \RuntimeException(\sprintf('Unable to tar %s, this file does not exist.', $what));
        }

        $tar = new \PharData($filename);

        if ($what instanceof Directory) {
            $prefixLength = \mb_strlen($what->path());

            foreach ($what->files() as $file) {
                $tar->addFromString(\mb_substr($file->path(), $prefixLength), $file->contents());
            }

            return $tar;
        }

        if ($what instanceof File) {
            $tar->addFromString($what->name(), $what->contents());

            return $tar;
        }

        \assert($what instanceof \SplFileInfo);

        if (!$what->isDir()) {
            $tar->addFile($what, $what->getFilename());

            return $tar;
        }

        foreach (Finder::create()->in((string) $what)->files() as $file) {
            $tar->addFile($file, $file->getRelativePathname());
        }

        return $tar;
    }
}

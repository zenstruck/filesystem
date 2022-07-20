<?php

namespace Zenstruck\Filesystem\Flysystem\Adapter;

use League\Flysystem\FilesystemAdapter;
use Zenstruck\Filesystem\Exception\UnsupportedFeature;
use Zenstruck\Filesystem\Feature\All;
use Zenstruck\Filesystem\Feature\FileChecksum;
use Zenstruck\Filesystem\Feature\FileUrl;
use Zenstruck\Filesystem\Feature\ModifyFile;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Uri;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class FeatureAwareAdapter extends WrappedAdapter implements All
{
    protected const FEATURES_ADDED = [];

    public function __construct(private FilesystemAdapter $next)
    {
    }

    public function md5ChecksumFor(File $file): string
    {
        return $this->ensureSupports(FileChecksum::class)->md5ChecksumFor($file); // @phpstan-ignore-line
    }

    public function sha1ChecksumFor(File $file): string
    {
        return $this->ensureSupports(FileChecksum::class)->sha1ChecksumFor($file); // @phpstan-ignore-line
    }

    public function modifyFile(File $file, callable $callback): \SplFileInfo
    {
        return $this->ensureSupports(ModifyFile::class)->modifyFile($file, $callback); // @phpstan-ignore-line
    }

    public function urlFor(File $file): Uri
    {
        return $this->ensureSupports(FileUrl::class)->urlFor($file); // @phpstan-ignore-line
    }

    final public function supports(string $feature): bool
    {
        if (\in_array($feature, static::FEATURES_ADDED, true)) {
            return true;
        }

        return $this->next instanceof self ? $this->next->supports($feature) : $this->next instanceof $feature;
    }

    protected function inner(): FilesystemAdapter
    {
        return $this->next;
    }

    /**
     * @return FilesystemAdapter The "real" adapter
     */
    private function adapter(): FilesystemAdapter
    {
        return $this->next instanceof self ? $this->next->adapter() : $this->next;
    }

    private function ensureSupports(string $feature): FilesystemAdapter
    {
        if (!$this->supports($feature)) {
            throw new UnsupportedFeature(\sprintf('The "%s" adapter does not support "%s".', \get_class($this->adapter()), $feature));
        }

        return $this->next;
    }
}

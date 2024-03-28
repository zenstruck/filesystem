<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;
use Zenstruck\Filesystem\Node\File\Image\LazyImage;
use Zenstruck\Filesystem\Node\File\LazyFile;
use Zenstruck\Filesystem\Node\Mapping;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type Format from Mapping
 * @phpstan-import-type Serialized from Mapping
 */
final class CacheFilesystem implements Filesystem
{
    use DecoratedFilesystem;

    private Mapping $mapping;

    /**
     * @param Format $metadata
     */
    public function __construct(
        private Filesystem $inner,
        private CacheItemPoolInterface $cache,
        array|string $metadata,
        private ?int $ttl = null,
    ) {
        // ensure PATH is always included
        if (\is_string($metadata) && Mapping::PATH !== $metadata) {
            $metadata = \array_merge((array) $metadata, [Mapping::PATH]);
        }

        if (\is_array($metadata) && !\in_array(Mapping::PATH, $metadata, true)) {
            $metadata[] = Mapping::PATH;
        }

        $this->mapping = new Mapping($metadata, filesystem: '__none__'); // dummy filesystem as it's required if not using a dsn (todo: remove this requirement)
    }

    public function node(string $path): File|Directory
    {
        $item = $this->cache->getItem($this->cacheKey($path));

        if ($item->isHit() && $file = $this->unserialize($item->get())) {
            return $file;
        }

        $node = $this->inner->node($path);

        if ($node instanceof Directory) {
            // directories are not cached
            return $node;
        }

        return $this->cache($node, $item);
    }

    public function file(string $path): File
    {
        return $this->node($path)->ensureFile();
    }

    public function image(string $path): Image
    {
        return $this->node($path)->ensureImage();
    }

    public function has(string $path): bool
    {
        if ($this->cache->hasItem($this->cacheKey($path))) {
            return true;
        }

        return $this->inner->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): File
    {
        return $this->cache($this->inner->copy($source, $destination, $config));
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        try {
            return $this->cache($this->inner->move($source, $destination, $config));
        } finally {
            $this->cache->deleteItem($this->cacheKey($source));
        }
    }

    public function delete(string $path, array $config = []): self
    {
        $this->inner->delete($path, $config);
        $this->cache->deleteItem($this->cacheKey($path));

        if ('' === $path && $this->cache instanceof TagAwareCacheInterface) {
            $this->cache->invalidateTags(["filesystem.{$this->name()}"]);
        }

        return $this;
    }

    public function chmod(string $path, string $visibility): File|Directory
    {
        $node = $this->inner->chmod($path, $visibility);

        if ($node instanceof Directory) {
            return $node;
        }

        return $this->cache($node);
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        return $this->cache($this->inner->write($path, $value, $config));
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }

    private function cache(File $file, ?CacheItemInterface $item = null): File
    {
        $item ??= $this->cache->getItem($this->cacheKey($file->path()));

        if ($this->ttl) {
            $item->expiresAfter($this->ttl);
        }

        if ($this->cache instanceof TagAwareCacheInterface && $item instanceof ItemInterface) {
            $item->tag(['filesystem', "filesystem.{$this->name()}"]);
        }

        $this->cache->save($item->set($this->serialize($file)));

        return $file;
    }

    /**
     * @return array{Serialized,bool}
     */
    private function serialize(File $file): array
    {
        return [$this->mapping->serialize($file), $file->isImage()];
    }

    private function unserialize(mixed $value): ?File
    {
        if (!\is_array($value) || 2 !== \count($parts = $value)) {
            return null;
        }

        [$data, $isImage] = $parts;

        $file = $isImage ? new LazyImage($data) : new LazyFile($data);
        $file->setFilesystem($this->inner);

        return $file;
    }

    private function cacheKey(string $path): string
    {
        return \sprintf('filesystem.%s.%s', $this->name(), \str_replace('/', '--', $path));
    }
}

<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type ConfigMapping from NodeConfigProvider
 */
final class CacheNodeConfigProvider implements NodeConfigProvider, CacheWarmerInterface
{
    /** @var array<class-string,list<ConfigMapping>> */
    private array $localCache;

    public function __construct(private NodeConfigProvider $inner, private CacheInterface $cache)
    {
    }

    public function configFor(string $class): array
    {
        return $this->localCache[$class] ??= $this->cache->get(self::createKey($class), fn() => $this->inner->configFor($class));
    }

    public function managedClasses(): iterable
    {
        return $this->inner->managedClasses();
    }

    /**
     * @return string[]
     */
    public function warmup(string $cacheDir): array
    {
        foreach ($this->managedClasses() as $class) {
            $this->configFor($class);
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    private static function createKey(string $class): string
    {
        return 'zs_node_cache_'.\str_replace('\\', '', $class);
    }
}

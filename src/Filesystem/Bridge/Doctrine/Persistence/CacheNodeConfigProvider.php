<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type ConfigMapping from NodeConfigProvider
 */
final class CacheNodeConfigProvider implements NodeConfigProvider
{
    /** @var array<class-string,list<ConfigMapping>> */
    private array $localCache;

    public function __construct(private NodeConfigProvider $inner, private CacheInterface $cache)
    {
    }

    public function configFor(string $class): array
    {
        return $this->localCache[$class] ??= $this->cache->get('zs_node_cache_'.$class, fn() => $this->inner->configFor($class));
    }

    public function managedClasses(): iterable
    {
        return $this->inner->managedClasses();
    }

    public function warmup(): void
    {
        foreach ($this->managedClasses() as $class) {
            $this->configFor($class);
        }
    }
}

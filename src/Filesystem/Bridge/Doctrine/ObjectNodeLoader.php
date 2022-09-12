<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine;

use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\ObjectReflector;
use Zenstruck\Filesystem\FilesystemRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ObjectNodeLoader
{
    /**
     * @internal
     */
    public function __construct(private FilesystemRegistry $registry, private NodeConfigProvider $config)
    {
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function load(object $object, ?string $property = null): object
    {
        if (!$config = $this->config->configFor($object::class)) {
            return $object;
        }

        (new ObjectReflector($object, $config))->load($this->registry, $property);

        return $object;
    }
}

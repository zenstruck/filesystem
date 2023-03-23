<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Doctrine;

use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Filesystem\Doctrine\EventListener\NodeLifecycleListener;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MappingManager
{
    /** @var \WeakMap<object,true> */
    private \WeakMap $loaded;

    /**
     * @internal
     */
    public function __construct(private ManagerRegistry $registry, private NodeLifecycleListener $listener)
    {
        $this->loaded = new \WeakMap();
    }

    /**
     * @template T of object|iterable
     *
     * @param T $object
     *
     * @return T
     */
    public function load(object|iterable $object): object|iterable
    {
        if (\is_iterable($object)) {
            foreach ($object as $item) {
                if (\is_object($item)) {
                    $this->load($item);
                }
            }

            return $object;
        }

        if (isset($this->loaded[$object])) {
            return $object;
        }

        if ($om = $this->registry->getManagerForClass($object::class)) {
            $this->listener->load($object, $om, force: true);
        }

        $this->loaded[$object] = true;

        return $object;
    }

    public function createQueryFile(object $object, string $property, File $file): File
    {
        if (!$om = $this->registry->getManagerForClass($object::class)) {
            throw new \InvalidArgumentException(\sprintf('Class "%s" is not managed by Doctrine.', $object::class));
        }

        return $this->listener->createQueryFile($object, $om, $property, $file);
    }
}

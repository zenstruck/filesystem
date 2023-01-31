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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileMappingLoader
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
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function __invoke(object $object): object
    {
        if (isset($this->loaded[$object])) {
            return $object;
        }

        if ($om = $this->registry->getManagerForClass($object::class)) {
            $this->listener->load($object, $om, force: true);
        }

        $this->loaded[$object] = true;

        return $object;
    }
}

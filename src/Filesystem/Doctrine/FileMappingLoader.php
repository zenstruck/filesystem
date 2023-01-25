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
    /**
     * @internal
     */
    public function __construct(private ManagerRegistry $registry, private NodeLifecycleListener $listener)
    {
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
        if (!$om = $this->registry->getManagerForClass($object::class)) {
            return $object;
        }

        $this->listener->load($object, $om, force: true);

        return $object;
    }
}

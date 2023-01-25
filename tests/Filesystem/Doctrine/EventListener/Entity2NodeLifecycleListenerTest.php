<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Doctrine\EventListener;

use Zenstruck\Tests\Filesystem\Symfony\Fixture\Entity\Entity2;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Entity2NodeLifecycleListenerTest extends NodeLifecycleListenerTest
{
    protected function entityClass(): string
    {
        return Entity2::class;
    }
}

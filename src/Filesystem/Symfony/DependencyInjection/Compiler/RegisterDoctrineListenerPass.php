<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class RegisterDoctrineListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('.zenstruck_filesystem.doctrine.lifecycle_listener') || !$container->hasParameter('doctrine.entity_managers')) {
            return;
        }

        $listener = $container->getDefinition('.zenstruck_filesystem.doctrine.lifecycle_listener');
        $managers = $container->getParameter('doctrine.entity_managers');

        \assert(\is_array($managers));

        foreach (\array_keys($managers) as $name) {
            $listener->addTag('doctrine.orm.entity_listener', ['entity_manager' => $name]);
        }
    }
}

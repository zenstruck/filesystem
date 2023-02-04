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
use Zenstruck\Filesystem\Doctrine\Types\FileDsnType;
use Zenstruck\Filesystem\Doctrine\Types\FileMetadataType;
use Zenstruck\Filesystem\Doctrine\Types\FilePathType;
use Zenstruck\Filesystem\Doctrine\Types\ImageDsnType;
use Zenstruck\Filesystem\Doctrine\Types\ImageMetadataType;
use Zenstruck\Filesystem\Doctrine\Types\ImagePathType;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class RegisterDoctrineTypePass implements CompilerPassInterface
{
    public const TYPES = [
        FilePathType::class,
        ImagePathType::class,
        FileDsnType::class,
        ImageDsnType::class,
        FileMetadataType::class,
        ImageMetadataType::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('doctrine.dbal.connection_factory.types')) {
            return;
        }

        /** @var array $typeDefinition */
        $typeDefinition = $container->getParameter('doctrine.dbal.connection_factory.types');

        foreach (self::TYPES as $type) {
            if (!isset($typeDefinition[$type::NAME])) {
                $typeDefinition[$type::NAME] = ['class' => $type];
            }
        }

        $container->setParameter('doctrine.dbal.connection_factory.types', $typeDefinition);
    }
}

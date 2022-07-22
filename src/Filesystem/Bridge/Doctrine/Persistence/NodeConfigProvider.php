<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types\FileCollectionType;
use Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types\FileType;
use Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types\ImageCollectionType;
use Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types\ImageType;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-type ConfigMapping = array<int,array{
 *      filesystem: string,
 *      property: string,
 *      delete_on_remove?: bool,
 *      namer?: string,
 *      expression?: string,
 * }>
 */
interface NodeConfigProvider
{
    public const NODE_TYPES = [FileType::NAME, ImageType::NAME, FileCollectionType::NAME, ImageCollectionType::NAME];

    /**
     * @param class-string $class
     *
     * @return ConfigMapping
     */
    public function configFor(string $class): array;

    /**
     * @return class-string[]
     */
    public function managedClasses(): iterable;
}

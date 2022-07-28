<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types\FileCollectionType;
use Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types\FileType;
use Zenstruck\Filesystem\Bridge\Doctrine\DBAL\Types\ImageType;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-type ConfigMapping = array{
 *      filesystem: string,
 *      property: string,
 *      autoload: bool|null,
 *      delete_on_remove: bool|null,
 *      delete_on_update: bool|null,
 *      write_on_update: bool|null,
 *      write_on_persist: bool|null,
 *      namer?: string,
 *      expression?: string,
 * }
 */
interface NodeConfigProvider
{
    public const NODE_TYPES = [FileType::NAME, ImageType::NAME, FileCollectionType::NAME];

    public const AUTOLOAD = 'autoload';
    public const WRITE_ON_PERSIST = 'write_on_persist';
    public const WRITE_ON_UPDATE = 'write_on_update';
    public const DELETE_ON_REMOVE = 'delete_on_remove';
    public const DELETE_ON_UPDATE = 'delete_on_update';

    /**
     * @param class-string $class
     *
     * @return array<string,ConfigMapping>
     */
    public function configFor(string $class): array;

    /**
     * @return class-string[]
     */
    public function managedClasses(): iterable;
}

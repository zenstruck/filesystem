<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine;

use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\NodeConfigProvider;
use Zenstruck\Filesystem\LazyFilesystem;
use Zenstruck\Filesystem\MultiFilesystem;
use Zenstruck\Filesystem\Node\File\LazyFileCollection;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ObjectNodeLoader
{
    /** @var LazyFilesystem[] */
    private array $lazyFilesystems = [];

    /**
     * @internal
     */
    public function __construct(private MultiFilesystem $filesystem, private NodeConfigProvider $config)
    {
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return T
     */
    public function load(object $object): object
    {
        if (!$configs = $this->config->configFor($object::class)) {
            return $object;
        }

        $refObj = new \ReflectionObject($object);

        foreach ($configs as $config) {
            // todo embedded?
            $refProp = $refObj->getProperty($config['property']);
            $refProp->setAccessible(true);

            if (!$refProp->isInitialized($object)) {
                continue;
            }

            $node = $refProp->getValue($object);

            if (!$node instanceof LazyNode && !$node instanceof LazyFileCollection) {
                continue;
            }

            $filesystemName = $config['filesystem'];
            $filesystem = $this->lazyFilesystems[$filesystemName] ??= new LazyFilesystem(fn() => $this->filesystem->get($filesystemName));

            $node->setFilesystem($filesystem);
        }

        return $object;
    }

    public function hasNodes(object $object): bool
    {
        return (bool) $this->config->configFor($object::class);
    }
}

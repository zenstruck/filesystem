<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Zenstruck\Filesystem\FilesystemRegistry;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\LazyNode;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type ConfigMapping from NodeConfigProvider
 */
final class ObjectReflector
{
    private \ReflectionObject $ref;

    /** @var array<string,\ReflectionProperty> */
    private array $properties = [];

    /**
     * @param array<string,ConfigMapping> $config
     */
    public function __construct(private object $object, private array $config)
    {
        $this->ref = new \ReflectionObject($object);
    }

    public function load(FilesystemRegistry $registry, ?string $property): void
    {
        if ($property && !isset($this->config[$property])) {
            throw new \InvalidArgumentException(\sprintf('Property "%s" is not configured as a file node on "%s".', $property, $this->object::class));
        }

        foreach ($property ? [$property => $this->config[$property]] : $this->config as $name => $config) {
            $node = $this->get($name);

            if (!$node instanceof LazyNode) {
                continue;
            }

            $node->setFilesystem($registry->get($config['filesystem']));
        }
    }

    public function set(string $property, Node $node): void
    {
        $this->property($property)->setValue($this->object, $node);
    }

    public function get(string $property): ?Node
    {
        $ref = $this->property($property);

        if (!$ref->isInitialized($this->object)) {
            return null;
        }

        $node = $ref->getValue($this->object);

        return ($node instanceof Node) ? $node : null;
    }

    private function property(string $name): \ReflectionProperty
    {
        // todo embedded

        if (\array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        $this->properties[$name] = $this->ref->getProperty($name);
        $this->properties[$name]->setAccessible(true);

        return $this->properties[$name];
    }
}

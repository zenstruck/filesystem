<?php

namespace Zenstruck\Filesystem\Bridge\Doctrine\Persistence;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ORMNodeConfigProvider implements NodeConfigProvider
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function configFor(string $class): array
    {
        $metadata = $this->em->getClassMetadata($class);
        $config = [];

        foreach ($metadata->fieldMappings as $mapping) {
            // todo embedded
            if (!\in_array($mapping['type'], self::NODE_TYPES, true)) {
                continue;
            }

            $config[$mapping['fieldName']] = [
                'filesystem' => $mapping['options']['filesystem'] ?? throw new \LogicException(\sprintf('Column definition for %s::$%s is missing the required "filesystem" option.', $class, $mapping['fieldName'])),
                'property' => $mapping['fieldName'],
                'namer' => $mapping['options']['namer'] ?? null,
                'expression' => $mapping['options']['expression'] ?? null,
                'template' => $mapping['options']['template'] ?? null,
                NodeConfigProvider::AUTOLOAD => $mapping['options'][NodeConfigProvider::AUTOLOAD] ?? null,
                NodeConfigProvider::DELETE_ON_REMOVE => $mapping['options'][NodeConfigProvider::DELETE_ON_REMOVE] ?? null,
                NodeConfigProvider::WRITE_ON_UPDATE => $mapping['options'][NodeConfigProvider::WRITE_ON_UPDATE] ?? null,
                NodeConfigProvider::DELETE_ON_UPDATE => $mapping['options'][NodeConfigProvider::DELETE_ON_UPDATE] ?? null,
                NodeConfigProvider::WRITE_ON_PERSIST => $mapping['options'][NodeConfigProvider::WRITE_ON_PERSIST] ?? null,
            ];
        }

        return $config;
    }

    public function managedClasses(): iterable
    {
        if (!$driver = $this->em->getConfiguration()->getMetadataDriverImpl()) {
            return [];
        }

        return $driver->getAllClassNames();
    }
}

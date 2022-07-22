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
    private function __construct(private EntityManagerInterface $em)
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

            $config[] = [
                'filesystem' => $mapping['options']['filesystem'] ?? throw new \LogicException(\sprintf('Column definition for %s::$%s is missing the required "filesystem" option.', $class, $mapping['fieldName'])),
                'property' => $mapping['options']['fieldName'],
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

<?php

namespace Zenstruck\Filesystem\Tests\Fixture\Symfony\Factory;

use Zenstruck\Filesystem\Tests\Fixture\Symfony\Entity\Entity1;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Entity1>
 *
 * @method static Entity1|Proxy     createOne(array $attributes = [])
 * @method static Entity1[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Entity1|Proxy     find(object|array|mixed $criteria)
 * @method static Entity1|Proxy     findOrCreate(array $attributes)
 * @method static Entity1|Proxy     first(string $sortedField = 'id')
 * @method static Entity1|Proxy     last(string $sortedField = 'id')
 * @method static Entity1|Proxy     random(array $attributes = [])
 * @method static Entity1|Proxy     randomOrCreate(array $attributes = []))
 * @method static Entity1[]|Proxy[] all()
 * @method static Entity1[]|Proxy[] findBy(array $attributes)
 * @method static Entity1[]|Proxy[] randomSet(int $number, array $attributes = []))
 * @method static Entity1[]|Proxy[] randomRange(int $min, int $max, array $attributes = []))
 * @method        Entity1|Proxy     create(array|callable $attributes = [])
 */
final class Entity1Factory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [];
    }

    protected static function getClass(): string
    {
        return Entity1::class;
    }
}

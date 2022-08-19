<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Feature\Image\MultiImageTransformer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class DefaultSet
{
    /** @var array<class-string,?object> */
    private static array $features = [];

    /**
     * @template T of object
     *
     * @param class-string<T> $name
     *
     * @return ?T
     */
    public static function get(string $name): ?object
    {
        return self::$features[$name] ??= match ($name) { // @phpstan-ignore-line
            ImageTransformer::class => new MultiImageTransformer(),
            default => null,
        };
    }
}

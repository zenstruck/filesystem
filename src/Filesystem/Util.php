<?php

namespace Zenstruck\Filesystem;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class Util
{
    private static Filesystem $fs;

    private function __construct()
    {
    }

    public static function fs(): Filesystem
    {
        return self::$fs ??= new Filesystem();
    }
}

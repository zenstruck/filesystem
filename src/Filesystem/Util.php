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
    private const MULTI_EXTENSIONS = ['gz' => 'tar.gz', 'bz2' => 'tar.bz2'];

    private static Filesystem $fs;

    private function __construct()
    {
    }

    public static function fs(): Filesystem
    {
        return self::$fs ??= new Filesystem();
    }

    /**
     * @return array{0:string,1:string|null}
     */
    public static function parseNameParts(string $filename): array
    {
        if (!$ext = \mb_strtolower(\pathinfo($filename, \PATHINFO_EXTENSION)) ?: null) {
            return [$filename, null];
        }

        if (isset(self::MULTI_EXTENSIONS[$ext]) && \str_ends_with($filename, self::MULTI_EXTENSIONS[$ext])) {
            $ext = self::MULTI_EXTENSIONS[$ext];
        }

        return [\mb_substr($filename, 0, -(\mb_strlen($ext) + 1)), $ext];
    }
}

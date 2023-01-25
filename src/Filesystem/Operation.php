<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @TODO make enum in PHP 8.1
 */
final class Operation
{
    public const READ = 'read';
    public const WRITE = 'write';
    public const MOVE = 'move';
    public const COPY = 'copy';
    public const DELETE = 'delete';
    public const CHMOD = 'chmod';
    public const MKDIR = 'mkdir';

    private function __construct()
    {
    }

    /**
     * @return array<self::*>
     */
    public static function all(): array
    {
        return [self::READ, self::WRITE, self::MOVE, self::COPY, self::DELETE, self::CHMOD, self::MKDIR];
    }

    /**
     * @return array<self::*>
     */
    public static function writes(): array
    {
        return [self::WRITE, self::MOVE, self::COPY, self::DELETE, self::CHMOD, self::MKDIR];
    }
}

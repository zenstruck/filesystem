<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test\Node\Foundry;

use Zenstruck\Filesystem\Test\Node\Mock;
use Zenstruck\Foundry\LazyValue;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LazyMock
{
    /**
     * @param ?string                      $filename Optional filename to use (must not include directory separators)
     * @param string|resource|\SplFileInfo $content
     */
    public static function pendingFile(?string $filename = null, ?string $extension = null, mixed $content = null): LazyValue
    {
        return new LazyValue(fn() => Mock::pendingFile($filename, $extension, $content));
    }

    /**
     * @param ?string $filename Optional filename to use (must not include directory separators)
     */
    public static function pendingImage(int $width = 10, int $height = 10, string $type = 'png', ?string $filename = null): LazyValue
    {
        return new LazyValue(fn() => Mock::pendingImage($width, $height, $type, $filename));
    }
}

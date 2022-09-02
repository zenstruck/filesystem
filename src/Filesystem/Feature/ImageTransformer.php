<?php

namespace Zenstruck\Filesystem\Feature;

use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @template T of object
 *
 * @phpstan-type TransformOptions = array{
 *     format?: string,
 * }
 */
interface ImageTransformer
{
    /**
     * @param callable(T):T    $manipulator
     * @param TransformOptions $options
     */
    public function transform(Image $image, callable $manipulator, array $options): \SplFileInfo;
}

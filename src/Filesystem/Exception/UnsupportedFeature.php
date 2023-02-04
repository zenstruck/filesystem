<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnsupportedFeature extends \LogicException
{
    /**
     * @internal
     *
     * @param class-string $feature
     */
    public function __construct(string $feature, string $filesystem, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Feature "%s" is not supported by filesystem "%s".', $feature, $filesystem), previous: $previous);
    }
}

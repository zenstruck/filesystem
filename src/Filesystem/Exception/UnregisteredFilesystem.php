<?php

namespace Zenstruck\Filesystem\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnregisteredFilesystem extends \RuntimeException
{
    public function __construct(string $name, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Filesystem "%s" is not registered.', $name), previous: $previous);
    }
}

<?php

namespace Zenstruck\Filesystem\Exception;

use League\Flysystem\FilesystemOperationFailed;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnableToCopyDirectory extends \RuntimeException implements FilesystemOperationFailed
{
    private function __construct(private string $source, private string $destination, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    public function source(): string
    {
        return $this->source;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    public static function fromLocationTo(string $source, string $destination, string $reason = '', ?\Throwable $previous = null): self
    {
        return new self($source, $destination, \sprintf('Unable to copy directory from "%s" to "%s". %s', $source, $destination, $reason), $previous);
    }

    public function operation(): string
    {
        return FilesystemOperationFailed::OPERATION_COPY;
    }
}

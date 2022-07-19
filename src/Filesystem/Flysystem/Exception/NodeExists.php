<?php

namespace Zenstruck\Filesystem\Flysystem\Exception;

use League\Flysystem\FilesystemException;
use Zenstruck\Filesystem\Node;
use Zenstruck\Filesystem\Node\File;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NodeExists extends \RuntimeException implements FilesystemException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forMove(string $source, Node $destination): self
    {
        return new self(\sprintf('Unable to move "%s" to "%s" as this %s already exists.', $source, $destination, $destination instanceof File ? 'file' : 'directory'));
    }

    public static function forCopy(string $source, Node $destination): self
    {
        return new self(\sprintf('Unable to copy "%s" to "%s" as this %s already exists.', $source, $destination, $destination instanceof File ? 'file' : 'directory'));
    }

    public static function forWrite(Node $path): self
    {
        return new self(\sprintf('Unable to write "%s" this %s already exists.', $path, $path instanceof File ? 'file' : 'directory'));
    }
}

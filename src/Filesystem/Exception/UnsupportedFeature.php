<?php

namespace Zenstruck\Filesystem\Exception;

use League\Flysystem\FilesystemException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UnsupportedFeature extends \RuntimeException implements FilesystemException
{
}

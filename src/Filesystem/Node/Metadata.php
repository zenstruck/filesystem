<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @TODO make enum in PHP 8.1
 */
final class Metadata
{
    public const PATH = 'path';
    public const DSN = 'dsn';
    public const LAST_MODIFIED = 'last_modified';
    public const VISIBILITY = 'visibility';
    public const MIME_TYPE = 'mime_type';
    public const SIZE = 'size';
    public const CHECKSUM = 'checksum';
    public const PUBLIC_URL = 'public_url';
    public const TRANSFORM_URL = 'transform_url';
    public const DIMENSIONS = 'dimensions';
    public const EXIF = 'exif';
    public const IPTC = 'iptc';

    private function __construct()
    {
    }
}

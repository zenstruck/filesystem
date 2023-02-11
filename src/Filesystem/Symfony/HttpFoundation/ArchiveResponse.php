<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\HttpFoundation;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Zenstruck\Filesystem\Archive\ZipFile;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArchiveResponse extends BinaryFileResponse
{
    private function __construct(\SplFileInfo $file, ?string $filename, int $status, array $headers, bool $public, bool $autoEtag, bool $autoLastModified)
    {
        parent::__construct($file, $status, $headers, $public, null, $autoEtag, $autoLastModified);

        $this->deleteFileAfterSend();

        if ($filename) {
            $this->setContentDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename);
        }
    }

    public static function zip(Node|\SplFileInfo|string $what, ?string $filename = 'archive.zip', array $config = [], int $status = 200, array $headers = [], bool $public = true, bool $autoEtag = false, bool $autoLastModified = true): self
    {
        return new self(ZipFile::zip($what, config: $config), $filename, $status, $headers, $public, $autoEtag, $autoLastModified);
    }
}

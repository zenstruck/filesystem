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

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Stream;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileResponse extends StreamedResponse
{
    public function __construct(File $file, int $status = 200, array $headers = [])
    {
        parent::__construct(static fn() => Stream::inOutput()->write($file->stream()), $status, $headers);

        if (!$this->headers->has('Last-Modified')) {
            $this->setLastModified($file->lastModified());
        }

        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', $file->mimeType());
        }
    }

    public static function inline(File $file, ?string $filename = null, int $status = 200, array $headers = []): self
    {
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename ?? $file->path()->name());

        return new self($file, $status, \array_merge($headers, ['Content-Disposition' => $disposition]));
    }

    public static function attachment(File $file, ?string $filename = null, int $status = 200, array $headers = []): self
    {
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename ?? $file->path()->name());

        return new self($file, $status, \array_merge($headers, ['Content-Disposition' => $disposition]));
    }
}

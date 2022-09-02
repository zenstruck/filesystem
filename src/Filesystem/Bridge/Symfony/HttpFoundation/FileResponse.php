<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\HttpFoundation;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Util\ResourceWrapper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileResponse extends StreamedResponse
{
    /**
     * @param array<string,string|string[]> $headers
     */
    public function __construct(File $file, int $status = 200, array $headers = [])
    {
        parent::__construct(static fn() => ResourceWrapper::inOutput()->write($file->read()), $status, $headers);

        if (!$this->headers->has('Last-Modified')) {
            $this->setLastModified($file->lastModified());
        }

        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', $file->mimeType());
        }
    }

    /**
     * @param array<string,string|string[]> $headers
     */
    public static function inline(File $file, ?string $filename = null, int $status = 200, array $headers = []): self
    {
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename ?? $file->name());

        return new self($file, $status, \array_merge($headers, ['Content-Disposition' => $disposition]));
    }

    /**
     * @param array<string,string|string[]> $headers
     */
    public static function attachment(File $file, ?string $filename = null, int $status = 200, array $headers = []): self
    {
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename ?? $file->name());

        return new self($file, $status, \array_merge($headers, ['Content-Disposition' => $disposition]));
    }
}

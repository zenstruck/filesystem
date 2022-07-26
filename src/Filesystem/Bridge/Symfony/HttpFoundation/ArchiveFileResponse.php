<?php

namespace Zenstruck\Filesystem\Bridge\Symfony\HttpFoundation;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Zenstruck\Filesystem\ArchiveFile;
use Zenstruck\Filesystem\Node;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @phpstan-import-type ZipConfig from ArchiveFile
 */
final class ArchiveFileResponse extends BinaryFileResponse
{
    /**
     * @param array<string,string|string[]> $headers
     */
    private function __construct(\SplFileInfo $file, ?string $filename, int $status, array $headers, bool $public, bool $autoEtag, bool $autoLastModified)
    {
        parent::__construct($file, $status, $headers, $public, null, $autoEtag, $autoLastModified);

        $this->deleteFileAfterSend();

        if ($filename) {
            $this->setContentDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename);
        }
    }

    /**
     * @param ZipConfig|array<string,mixed> $config
     * @param array<string,string|string[]> $headers
     */
    public static function zip(Node|\SplFileInfo|string $what, ?string $filename = 'archive.zip', array $config = [], int $status = 200, array $headers = [], bool $public = true, bool $autoEtag = false, bool $autoLastModified = true): self
    {
        return new self(ArchiveFile::zip($what, config: $config), $filename, $status, $headers, $public, $autoEtag, $autoLastModified);
    }

    /**
     * @param array<string,string|string[]> $headers
     */
    public static function tar(Node|\SplFileInfo|string $what, ?string $filename = 'archive.tar', int $status = 200, array $headers = [], bool $public = true, bool $autoEtag = false, bool $autoLastModified = true): self
    {
        return new self(ArchiveFile::tar($what), $filename, $status, $headers, $public, $autoEtag, $autoLastModified);
    }

    /**
     * @param array<string,string|string[]> $headers
     */
    public static function tarGz(Node|\SplFileInfo|string $what, ?string $filename = 'archive.tar.gz', int $status = 200, array $headers = [], bool $public = true, bool $autoEtag = false, bool $autoLastModified = true): self
    {
        return new self(ArchiveFile::tarGz($what), $filename, $status, $headers, $public, $autoEtag, $autoLastModified);
    }

    /**
     * @param array<string,string|string[]> $headers
     */
    public static function tarBz2(Node|\SplFileInfo|string $what, ?string $filename = 'archive.tar.bz2', int $status = 200, array $headers = [], bool $public = true, bool $autoEtag = false, bool $autoLastModified = true): self
    {
        return new self(ArchiveFile::tarBz2($what), $filename, $status, $headers, $public, $autoEtag, $autoLastModified);
    }
}

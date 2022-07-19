<?php

namespace Zenstruck\Filesystem\Flysystem\Adapter;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;
use Zenstruck\Filesystem\Feature\FileChecksum;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LocalAdapter extends LocalFilesystemAdapter implements FileChecksum
{
    private PathPrefixer $prefixer;

    public function __construct(
        private string $location,
        ?VisibilityConverter $visibility = null,
        int $writeFlags = \LOCK_EX,
        int $linkHandling = self::DISALLOW_LINKS,
        ?MimeTypeDetector $mimeTypeDetector = null,
        bool $lazyRootCreation = false
    ) {
        parent::__construct($location, $visibility, $writeFlags, $linkHandling, $mimeTypeDetector, $lazyRootCreation);
    }

    public function md5Checksum(string $path): string
    {
        return \md5_file($this->prefixer()->prefixPath($path)) ?: throw UnableToRetrieveMetadata::create($path, 'md5_checksum');
    }

    public function sha1Checksum(string $path): string
    {
        return \sha1_file($this->prefixer()->prefixPath($path)) ?: throw UnableToRetrieveMetadata::create($path, 'sha1_checksum');
    }

    private function prefixer(): PathPrefixer
    {
        return $this->prefixer ??= new PathPrefixer($this->location, \DIRECTORY_SEPARATOR);
    }
}

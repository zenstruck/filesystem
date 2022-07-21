<?php

namespace Zenstruck\Filesystem\Flysystem\Adapter;

use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PathPrefixer;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnixVisibility\VisibilityConverter;
use League\MimeTypeDetection\MimeTypeDetector;
use Zenstruck\Filesystem\Feature\FileChecksum;
use Zenstruck\Filesystem\Feature\ModifyFile;
use Zenstruck\Filesystem\Node\File;

/**
 * Similar to Flysystem's {@see LocalFilesystemAdapter} but with extra features.
 *
 * - Efficient checksum calculation using {@see md5_file()}/{@see sha1_file()}
 * - Can modify files "in place" instead of writing to a temporary file
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LocalAdapter extends LocalFilesystemAdapter implements FileChecksum, ModifyFile
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

    public function md5ChecksumFor(File $file): string
    {
        return \md5_file($this->prefixer()->prefixPath($file)) ?: throw UnableToRetrieveMetadata::create($file, 'md5_checksum');
    }

    public function sha1ChecksumFor(File $file): string
    {
        return \sha1_file($this->prefixer()->prefixPath($file)) ?: throw UnableToRetrieveMetadata::create($file, 'sha1_checksum');
    }

    public function realFile(File $file): \SplFileInfo
    {
        return new \SplFileInfo($this->prefixer()->prefixPath($file));
    }

    private function prefixer(): PathPrefixer
    {
        return $this->prefixer ??= new PathPrefixer($this->location, \DIRECTORY_SEPARATOR);
    }
}

<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Node\Directory;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\Image;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LoggableFilesystem implements Filesystem
{
    use DecoratedFilesystem;

    public const DEFAULT_CONFIG = [
        Operation::READ => LogLevel::DEBUG,
        Operation::WRITE => LogLevel::INFO,
    ];

    /**
     * @param array<Operation::*,false|LogLevel::*> $config
     */
    public function __construct(
        private Filesystem $inner,
        private LoggerInterface $logger,
        private array $config = [],
    ) {
    }

    public function node(string $path): Node
    {
        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Reading "{path}" (node) on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return $this->inner()->node($path);
    }

    public function file(string $path): File
    {
        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Reading "{path}" (file) on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return $this->inner()->file($path);
    }

    public function directory(string $path = ''): Directory
    {
        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Reading "{path}" (directory) on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return $this->inner()->directory($path);
    }

    public function image(string $path): Image
    {
        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Reading "{path}" (image) on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return $this->inner()->image($path);
    }

    public function has(string $path): bool
    {
        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Checking existence of "{path}" on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return $this->inner()->has($path);
    }

    public function copy(string $source, string $destination, array $config = []): File
    {
        $this->log(
            $this->config[Operation::COPY] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Copying "{source}" to "{destination}" on filesystem "{filesystem}"',
            [
                'source' => $source,
                'destination' => $destination,
            ]
        );

        return $this->inner()->copy($source, $destination, $config);
    }

    public function move(string $source, string $destination, array $config = []): File
    {
        $this->log(
            $this->config[Operation::MOVE] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Moving "{source}" to "{destination}" on filesystem "{filesystem}"',
            [
                'source' => $source,
                'destination' => $destination,
            ]
        );

        return $this->inner()->move($source, $destination, $config);
    }

    public function delete(string $path, array $config = []): static
    {
        $this->log(
            $this->config[Operation::DELETE] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Deleting "{path}" on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        $this->inner()->delete($path, $config);

        return $this;
    }

    public function mkdir(string $path, Directory|\SplFileInfo|null $content = null, array $config = []): Directory
    {
        $this->log(
            $this->config[Operation::MKDIR] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Creating directory "{path}" on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return $this->inner()->mkdir($path, $content, $config);
    }

    public function chmod(string $path, string $visibility): Node
    {
        $this->log(
            $this->config[Operation::CHMOD] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Setting visibility of "{path}" to "{visibility}" on filesystem "{filesystem}"',
            [
                'path' => $path,
                'visibility' => $visibility,
            ]
        );

        return $this->inner()->chmod($path, $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): File
    {
        $this->log(
            $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Writing "{what}" to "{path}" on filesystem "{filesystem}"',
            [
                'what' => \get_debug_type($value),
                'path' => $path,
            ]
        );

        return $this->inner()->write($path, $value, $config);
    }

    protected function inner(): Filesystem
    {
        return $this->inner;
    }

    /**
     * @param LogLevel::*|false   $level
     * @param array<string,mixed> $context
     */
    private function log(string|bool $level, string $message, array $context = []): void
    {
        if (\is_bool($level)) {
            return;
        }

        $context['filesystem'] = $this->name();

        $this->logger->log($level, $message, $context);
    }
}

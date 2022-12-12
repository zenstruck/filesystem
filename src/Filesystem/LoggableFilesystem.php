<?php

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
final class LoggableFilesystem extends DecoratedFilesystem
{
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
        private array $config = []
    ) {
    }

    public function node(string $path): File|Directory
    {
        $this->log(
            $this->config[Operation::READ] ?? self::DEFAULT_CONFIG[Operation::READ],
            'Reading "{path}" (node) on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return parent::node($path);
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

        return parent::file($path);
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

        return parent::directory($path);
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

        return parent::image($path);
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

        return parent::has($path);
    }

    public function copy(string $source, string $destination, array $config = []): static
    {
        $this->log(
            $this->config[Operation::COPY] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Copying "{source}" to "{destination}" on filesystem "{filesystem}"',
            [
                'source' => $source,
                'destination' => $destination,
            ]
        );

        return parent::copy($source, $destination, $config);
    }

    public function move(string $source, string $destination, array $config = []): static
    {
        $this->log(
            $this->config[Operation::MOVE] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Moving "{source}" to "{destination}" on filesystem "{filesystem}"',
            [
                'source' => $source,
                'destination' => $destination,
            ]
        );

        return parent::move($source, $destination, $config);
    }

    public function delete(Directory|string $path, array $config = []): static
    {
        $this->log(
            $this->config[Operation::DELETE] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Deleting "{path}" on filesystem "{filesystem}"',
            [
                'path' => $path instanceof Directory ? $path->path() : $path,
            ]
        );

        return parent::delete($path, $config);
    }

    public function mkdir(string $path, array $config = []): static
    {
        $this->log(
            $this->config[Operation::MKDIR] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Creating directory "{path}" on filesystem "{filesystem}"',
            [
                'path' => $path,
            ]
        );

        return parent::mkdir($path, $config);
    }

    public function chmod(string $path, string $visibility): static
    {
        $this->log(
            $this->config[Operation::CHMOD] ?? $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Setting visibility of "{path}" to "{visibility}" on filesystem "{filesystem}"',
            [
                'path' => $path,
                'visibility' => $visibility,
            ]
        );

        return parent::chmod($path, $visibility);
    }

    public function write(string $path, mixed $value, array $config = []): static
    {
        $this->log(
            $this->config[Operation::WRITE] ?? self::DEFAULT_CONFIG[Operation::WRITE],
            'Writing "{what}" to "{path}" on filesystem "{filesystem}"',
            [
                'what' => \get_debug_type($value),
                'path' => $path,
            ]
        );

        return parent::write($path, $value, $config);
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

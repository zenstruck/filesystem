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

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\UnregisteredFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemRegistry
{
    /** @var array<string,Filesystem> */
    private array $filesystems;
    private ?ContainerInterface $locator;

    /**
     * @param ContainerInterface|array<string,Filesystem> $filesystems
     */
    public function __construct(ContainerInterface|array $filesystems)
    {
        $this->filesystems = \is_array($filesystems) ? $filesystems : [];
        $this->locator = $filesystems instanceof ContainerInterface ? $filesystems : null;
    }

    /**
     * @throws UnregisteredFilesystem
     */
    public function get(string $name): Filesystem
    {
        if (isset($this->filesystems[$name])) {
            return $this->filesystems[$name];
        }

        if (!$this->locator) {
            throw new UnregisteredFilesystem($name);
        }

        return $this->filesystems[$name] = new LazyFilesystem(fn() => $this->resolveFilesystem($name));
    }

    public function reset(): void
    {
        if ($this->locator) {
            $this->filesystems = [];
        }
    }

    private function resolveFilesystem(string $name): Filesystem
    {
        try {
            return $this->locator?->get($name) ?: throw new UnregisteredFilesystem($name);
        } catch (NotFoundExceptionInterface $e) {
            throw new UnregisteredFilesystem($name, $e);
        }
    }
}

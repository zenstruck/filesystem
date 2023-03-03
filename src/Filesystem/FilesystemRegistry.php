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
use Symfony\Contracts\Service\ServiceProviderInterface;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Exception\UnregisteredFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FilesystemRegistry
{
    /**
     * @param array<string,Filesystem>|ContainerInterface $filesystems
     */
    public function __construct(private array|ContainerInterface $filesystems)
    {
    }

    /**
     * @throws UnregisteredFilesystem
     */
    public function get(string $name): Filesystem
    {
        if (\is_array($this->filesystems)) {
            return $this->filesystems[$name] ?? throw new UnregisteredFilesystem($name);
        }

        try {
            return $this->filesystems->get($name);
        } catch (NotFoundExceptionInterface $e) {
            throw new UnregisteredFilesystem($name, $e);
        }
    }

    public function has(string $name): bool
    {
        return \is_array($this->filesystems) ? isset($this->filesystems[$name]) : $this->filesystems->has($name);
    }

    /**
     * @return string[] The names of the registered filesystems
     *
     * @throws \LogicException If unable to determine names
     */
    public function names(): array
    {
        if (\is_array($this->filesystems)) {
            return \array_keys($this->filesystems);
        }

        if ($this->filesystems instanceof ServiceProviderInterface) {
            return \array_keys($this->filesystems->getProvidedServices());
        }

        throw new \LogicException('Unable to determine registered filesystem names.');
    }
}

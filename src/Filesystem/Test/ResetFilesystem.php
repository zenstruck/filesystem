<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Test;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\FilesystemRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait ResetFilesystem
{
    /**
     * @before
     * @internal
     */
    public function _resetFilesystems(): void
    {
        if (!\method_exists($this, '_unsetFilesystem')) {
            throw new \LogicException(\sprintf('Can only use %s in conjunction with %s.', __TRAIT__, InteractsWithFilesystem::class));
        }

        if ($this instanceof KernelTestCase && !$this instanceof FilesystemProvider) {
            if (self::getContainer()->hasParameter('zenstruck_filesystem.reset_before_tests_filesystems')) {
                $registry = self::getContainer()->get(FilesystemRegistry::class);

                // delete all test filesystems
                foreach (self::getContainer()->getParameter('zenstruck_filesystem.reset_before_tests_filesystems') as $name) {
                    $registry->get($name)->delete('');
                }
            }

            self::ensureKernelShutdown();

            return;
        }

        $this->filesystem()->delete('');
        unset($this->_testFilesystem);
    }
}

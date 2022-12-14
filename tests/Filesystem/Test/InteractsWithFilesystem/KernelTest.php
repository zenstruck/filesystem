<?php

namespace Zenstruck\Tests\Filesystem\Test\InteractsWithFilesystem;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Tests\Filesystem\Test\InteractsWithFilesystemTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class KernelTest extends KernelTestCase
{
    use InteractsWithFilesystemTests;

    /**
     * @before
     */
    public function _resetFilesystems(): void
    {
        $this->markTestIncomplete('bundle not complete');
    }
}

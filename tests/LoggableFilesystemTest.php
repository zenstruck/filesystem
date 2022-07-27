<?php

namespace Zenstruck\Filesystem\Tests;

use Psr\Log\NullLogger;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\LoggableFilesystem;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LoggableFilesystemTest extends FilesystemTest
{
    use InteractsWithFilesystem;

    /**
     * @test
     */
    public function default_logging(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function can_customize_logging_configuration(): void
    {
        $this->markTestIncomplete();
    }

    protected function createFilesystem(): Filesystem
    {
        return new LoggableFilesystem($this->filesystem(), new NullLogger());
    }
}

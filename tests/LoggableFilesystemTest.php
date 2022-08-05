<?php

namespace Zenstruck\Filesystem\Tests;

use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\Log\Logger;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\LoggableFilesystem;
use Zenstruck\Filesystem\ResourceWrapper;
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
        $resource = ResourceWrapper::inMemory();
        $logger = new Logger(LogLevel::DEBUG, $resource->get());

        $filesystem = (new LoggableFilesystem($this->filesystem(), $logger))
            ->write('foo', 'bar')
            ->mkdir('bar')
            ->chmod('foo', 'public')
            ->copy('foo', 'file.png')
            ->delete('foo')
            ->move('file.png', 'file2.png')
        ;

        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $log = $resource->contents();
        $resource->close();

        $this->assertStringContainsString('[debug] Read "file2.png" (file) on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Read "file2.png" (image) on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Read "bar" (directory) on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Checked existence of "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Wrote "string" to "foo" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Created directory "bar" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Set visibility of "foo" to "public" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Copied "foo" to "file.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Moved "file.png" to "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Deleted "foo" on filesystem "default"', $log);
    }

    /**
     * @test
     */
    public function can_customize_logging_configuration(): void
    {
        $resource = ResourceWrapper::inMemory();
        $logger = new Logger(LogLevel::DEBUG, $resource->get());
        $filesystem = new LoggableFilesystem($this->filesystem(), $logger, [
            'read' => LogLevel::INFO,
            'write' => LogLevel::DEBUG,
            'move' => LogLevel::ALERT,
            'copy' => LogLevel::CRITICAL,
            'delete' => LogLevel::EMERGENCY,
            'chmod' => LogLevel::ERROR,
            'mkdir' => LogLevel::NOTICE,
        ]);

        $filesystem
            ->write('foo', 'bar')
            ->mkdir('bar')
            ->chmod('foo', 'public')
            ->copy('foo', 'file.png')
            ->delete('foo')
            ->move('file.png', 'file2.png')
        ;

        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $log = $resource->contents();
        $resource->close();

        $this->assertStringContainsString('[info] Read "file2.png" (file) on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Read "file2.png" (image) on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Read "bar" (directory) on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Checked existence of "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Wrote "string" to "foo" on filesystem "default"', $log);
        $this->assertStringContainsString('[notice] Created directory "bar" on filesystem "default"', $log);
        $this->assertStringContainsString('[error] Set visibility of "foo" to "public" on filesystem "default"', $log);
        $this->assertStringContainsString('[critical] Copied "foo" to "file.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[alert] Moved "file.png" to "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[emergency] Deleted "foo" on filesystem "default"', $log);
    }

    /**
     * @test
     */
    public function can_disable_logging_for_operations(): void
    {
        $resource = ResourceWrapper::inMemory();
        $logger = new Logger(LogLevel::DEBUG, $resource->get());

        $filesystem = (new LoggableFilesystem($this->filesystem(), $logger, ['read' => false]))
            ->write('foo', 'bar')
            ->mkdir('bar')
            ->chmod('foo', 'public')
            ->copy('foo', 'file.png')
            ->delete('foo')
            ->move('file.png', 'file2.png')
        ;

        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $log = $resource->contents();
        $resource->close();

        $this->assertStringNotContainsString('Read "file2.png" (file) on filesystem "default"', $log);
        $this->assertStringNotContainsString('Read "file2.png" (image) on filesystem "default"', $log);
        $this->assertStringNotContainsString('Read "bar" (directory) on filesystem "default"', $log);
        $this->assertStringNotContainsString('Checked existence of "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Wrote "string" to "foo" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Created directory "bar" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Set visibility of "foo" to "public" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Copied "foo" to "file.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Moved "file.png" to "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Deleted "foo" on filesystem "default"', $log);
    }

    protected function createFilesystem(): Filesystem
    {
        return new LoggableFilesystem($this->filesystem(), new NullLogger());
    }
}

<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem;

use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\Log\Logger;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\LoggableFilesystem;
use Zenstruck\Filesystem\Operation;
use Zenstruck\Stream;
use Zenstruck\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LoggableFilesystemTest extends FilesystemTest
{
    /**
     * @test
     */
    public function default_logging(): void
    {
        $resource = Stream::inMemory();
        $logger = new Logger(LogLevel::DEBUG, $resource->get());

        $filesystem = (new LoggableFilesystem(in_memory_filesystem(), $logger));
        $filesystem->write('foo', 'bar');
        $filesystem->mkdir('bar');
        $filesystem->chmod('foo', 'public');
        $filesystem->copy('foo', 'file.png');
        $filesystem->delete('foo');
        $filesystem->move('file.png', 'file2.png');
        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $log = $resource->contents();
        $resource->close();

        $this->assertStringContainsString('[debug] Reading "file2.png" (file) on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Reading "file2.png" (image) on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Reading "bar" (directory) on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Checking existence of "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Writing "string" to "foo" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Creating directory "bar" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Setting visibility of "foo" to "public" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Copying "foo" to "file.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Moving "file.png" to "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Deleting "foo" on filesystem "default"', $log);
    }

    /**
     * @test
     */
    public function can_customize_logging_configuration(): void
    {
        $resource = Stream::inMemory();
        $logger = new Logger(LogLevel::DEBUG, $resource->get());
        $filesystem = new LoggableFilesystem(in_memory_filesystem(), $logger, [
            Operation::READ => LogLevel::INFO,
            Operation::WRITE => LogLevel::DEBUG,
            Operation::MOVE => LogLevel::ALERT,
            Operation::COPY => LogLevel::CRITICAL,
            Operation::DELETE => LogLevel::EMERGENCY,
            Operation::CHMOD => LogLevel::ERROR,
            Operation::MKDIR => LogLevel::NOTICE,
        ]);

        $filesystem->write('foo', 'bar');
        $filesystem->mkdir('bar');
        $filesystem->chmod('foo', 'public');
        $filesystem->copy('foo', 'file.png');
        $filesystem->delete('foo');
        $filesystem->move('file.png', 'file2.png');
        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $log = $resource->contents();
        $resource->close();

        $this->assertStringContainsString('[info] Reading "file2.png" (file) on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Reading "file2.png" (image) on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Reading "bar" (directory) on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Checking existence of "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[debug] Writing "string" to "foo" on filesystem "default"', $log);
        $this->assertStringContainsString('[notice] Creating directory "bar" on filesystem "default"', $log);
        $this->assertStringContainsString('[error] Setting visibility of "foo" to "public" on filesystem "default"', $log);
        $this->assertStringContainsString('[critical] Copying "foo" to "file.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[alert] Moving "file.png" to "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[emergency] Deleting "foo" on filesystem "default"', $log);
    }

    /**
     * @test
     */
    public function can_disable_logging_for_operations(): void
    {
        $resource = Stream::inMemory();
        $logger = new Logger(LogLevel::DEBUG, $resource->get());

        $filesystem = (new LoggableFilesystem(in_memory_filesystem(), $logger, [Operation::READ => false]));
        $filesystem->write('foo', 'bar');
        $filesystem->mkdir('bar');
        $filesystem->chmod('foo', 'public');
        $filesystem->copy('foo', 'file.png');
        $filesystem->delete('foo');
        $filesystem->move('file.png', 'file2.png');
        $filesystem->node('file2.png');
        $filesystem->file('file2.png');
        $filesystem->image('file2.png');
        $filesystem->directory('bar');
        $filesystem->has('file2.png');

        $log = $resource->contents();
        $resource->close();

        $this->assertStringNotContainsString('Reading "file2.png" (file) on filesystem "default"', $log);
        $this->assertStringNotContainsString('Reading "file2.png" (image) on filesystem "default"', $log);
        $this->assertStringNotContainsString('Reading "bar" (directory) on filesystem "default"', $log);
        $this->assertStringNotContainsString('Checking existence of "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Writing "string" to "foo" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Creating directory "bar" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Setting visibility of "foo" to "public" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Copying "foo" to "file.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Moving "file.png" to "file2.png" on filesystem "default"', $log);
        $this->assertStringContainsString('[info] Deleting "foo" on filesystem "default"', $log);
    }

    protected function createFilesystem(): Filesystem
    {
        return new LoggableFilesystem(in_memory_filesystem(), new NullLogger());
    }
}

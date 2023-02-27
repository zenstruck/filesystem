<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Test\Node;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Test\Node\Mock;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MockTest extends TestCase
{
    /**
     * @test
     */
    public function pending_file(): void
    {
        $file = Mock::pendingFile();
        $this->assertFileExists($file);
        $this->assertSame('', $file->contents());
        $this->assertNull($file->path()->extension());

        $file = Mock::pendingFile(extension: 'txt');
        $this->assertFileExists($file);
        $this->assertSame('', $file->contents());
        $this->assertSame('txt', $file->path()->extension());

        $file = Mock::pendingFile(extension: 'txt', content: 'content');
        $this->assertFileExists($file);
        $this->assertSame('content', $file->contents());
        $this->assertSame('txt', $file->path()->extension());

        $file = Mock::pendingFile(content: 'content');
        $this->assertFileExists($file);
        $this->assertSame('content', $file->contents());
        $this->assertNull($file->path()->extension());

        $file = Mock::pendingFile(filename: 'my-file.txt');
        $this->assertFileExists($file);
        $this->assertSame('', $file->contents());
        $this->assertSame('my-file.txt', $file->path()->name());

        $file = Mock::pendingFile(filename: 'my-file.txt', content: 'content');
        $this->assertFileExists($file);
        $this->assertSame('content', $file->contents());
        $this->assertSame('my-file.txt', $file->path()->name());
    }

    /**
     * @test
     */
    public function pending_image(): void
    {
        $file = Mock::pendingImage();
        $this->assertFileExists($file);
        $this->assertSame(10, $file->dimensions()->width());
        $this->assertSame(10, $file->dimensions()->height());
        $this->assertSame('image/png', $file->mimeType());
        $this->assertSame('png', $file->path()->extension());

        $file = Mock::pendingImage(5, 20, type: 'jpg');
        $this->assertFileExists($file);
        $this->assertSame(5, $file->dimensions()->width());
        $this->assertSame(20, $file->dimensions()->height());
        $this->assertSame('image/jpeg', $file->mimeType());
        $this->assertSame('jpg', $file->path()->extension());

        $file = Mock::pendingImage(type: 'jpg', filename: 'my-image.png');
        $this->assertFileExists($file);
        $this->assertSame(10, $file->dimensions()->width());
        $this->assertSame(10, $file->dimensions()->height());
        $this->assertSame('image/png', $file->mimeType());
        $this->assertSame('my-image.png', $file->path()->name());
    }
}

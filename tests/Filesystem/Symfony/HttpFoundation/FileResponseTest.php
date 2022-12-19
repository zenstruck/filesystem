<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\HttpFoundation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Symfony\HttpFoundation\FileResponse;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileResponseTest extends TestCase
{
    /**
     * @test
     */
    public function can_create(): void
    {
        $file = $this->createFile();

        \ob_start();
        $response = (new FileResponse($file))->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertSame($file->lastModified()->format('Y-m-d O'), (new \DateTime($response->headers->get('last-modified')))->format('Y-m-d O'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertStringContainsString($file->mimeType(), $response->headers->get('content-type'));
        $this->assertFalse($response->headers->has('content-disposition'));
        $this->assertSame($file->contents(), $output);
    }

    /**
     * @test
     */
    public function can_create_as_inline(): void
    {
        $file = $this->createFile();

        \ob_start();
        $response = FileResponse::inline($file)->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertSame($file->lastModified()->format('Y-m-d O'), (new \DateTime($response->headers->get('last-modified')))->format('Y-m-d O'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertStringContainsString($file->mimeType(), $response->headers->get('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame("inline; filename={$file->path()->name()}", $response->headers->get('content-disposition'));
        $this->assertSame($file->contents(), $output);
    }

    /**
     * @test
     */
    public function can_create_as_attachment(): void
    {
        $file = $this->createFile();

        \ob_start();
        $response = FileResponse::attachment($file)->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertSame($file->lastModified()->format('Y-m-d O'), (new \DateTime($response->headers->get('last-modified')))->format('Y-m-d O'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertStringContainsString($file->mimeType(), $response->headers->get('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame("attachment; filename={$file->path()->name()}", $response->headers->get('content-disposition'));
        $this->assertSame($file->contents(), $output);
    }

    private function createFile(): File
    {
        return in_memory_filesystem()
            ->write('some/file.txt', 'content')
            ->last()
            ->ensureFile()
        ;
    }
}

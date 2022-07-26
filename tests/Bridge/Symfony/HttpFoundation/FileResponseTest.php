<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\HttpFoundation;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Filesystem\Bridge\Symfony\HttpFoundation\FileResponse;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Test\InteractsWithFilesystem;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class FileResponseTest extends TestCase
{
    use InteractsWithFilesystem;

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
        $this->assertSame("inline; filename={$file->name()}", $response->headers->get('content-disposition'));
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
        $this->assertSame("attachment; filename={$file->name()}", $response->headers->get('content-disposition'));
        $this->assertSame($file->contents(), $output);
    }

    private function createFile(): File
    {
        return $this->filesystem()
            ->write('some/file.txt', 'content')
            ->last()
            ->ensureFile()
        ;
    }
}

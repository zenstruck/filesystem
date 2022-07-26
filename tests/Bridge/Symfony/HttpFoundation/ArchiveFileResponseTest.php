<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\HttpFoundation;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Filesystem\Bridge\Symfony\HttpFoundation\ArchiveFileResponse;
use Zenstruck\Filesystem\Tests\FilesystemTest;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArchiveFileResponseTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_zip_response(): void
    {
        \ob_start();
        $response = ArchiveFileResponse::zip(FilesystemTest::FIXTURE_DIR.'/symfony.png')->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame('attachment; filename=archive.zip', $response->headers->get('content-disposition'));
        $this->assertSame('application/zip', $response->headers->get('content-type'));
        $this->assertSame('application/zip', (new FinfoMimeTypeDetector())->detectMimeTypeFromBuffer($output));
    }

    /**
     * @test
     */
    public function can_create_tar_response(): void
    {
        \ob_start();
        $response = ArchiveFileResponse::tar(FilesystemTest::FIXTURE_DIR.'/symfony.png')->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame('attachment; filename=archive.tar', $response->headers->get('content-disposition'));
        $this->assertSame('application/x-tar', $response->headers->get('content-type'));
        $this->assertSame('application/x-tar', (new FinfoMimeTypeDetector())->detectMimeTypeFromBuffer($output));
    }

    /**
     * @test
     */
    public function can_create_tar_gz_response(): void
    {
        \ob_start();
        $response = ArchiveFileResponse::tarGz(FilesystemTest::FIXTURE_DIR.'/symfony.png')->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame('attachment; filename=archive.tar.gz', $response->headers->get('content-disposition'));
        $this->assertSame('application/gzip', $response->headers->get('content-type'));
        $this->assertSame('application/gzip', (new FinfoMimeTypeDetector())->detectMimeTypeFromBuffer($output));
    }

    /**
     * @test
     */
    public function can_create_tar_bz2_response(): void
    {
        \ob_start();
        $response = ArchiveFileResponse::tarBz2(FilesystemTest::FIXTURE_DIR.'/symfony.png')->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame('attachment; filename=archive.tar.bz2', $response->headers->get('content-disposition'));
        $this->assertSame('application/x-bzip2', $response->headers->get('content-type'));
        $this->assertSame('application/x-bzip2', (new FinfoMimeTypeDetector())->detectMimeTypeFromBuffer($output));
    }
}

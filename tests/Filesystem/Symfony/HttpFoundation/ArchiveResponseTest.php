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

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Filesystem\Symfony\HttpFoundation\ArchiveResponse;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ArchiveResponseTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_zip_response(): void
    {
        \ob_start();
        $response = ArchiveResponse::zip(fixture('symfony.png'))->prepare(Request::create(''))->send();
        $output = \ob_get_clean();

        $this->assertTrue($response->headers->has('last-modified'));
        $this->assertTrue($response->headers->has('content-type'));
        $this->assertTrue($response->headers->has('content-disposition'));
        $this->assertSame('attachment; filename=archive.zip', $response->headers->get('content-disposition'));
        $this->assertSame('application/zip', $response->headers->get('content-type'));
        $this->assertSame('application/zip', (new FinfoMimeTypeDetector())->detectMimeTypeFromBuffer($output));
    }
}

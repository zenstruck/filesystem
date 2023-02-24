<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\HttpKernel;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Symfony\HttpKernel\RequestFilesExtractor;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class RequestFilesExtractorTest extends TestCase
{
    /**
     * @test
     */
    public function returns_null_for_empty_request(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');

        self::assertNull(
            $extractor->extractFilesFromRequest($request, 'file')
        );
    }

    /**
     * @test
     */
    public function returns_null_for_empty_path(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');
        $request->files->set('upload', self::uploadedFile());

        self::assertNull(
            $extractor->extractFilesFromRequest($request, 'file')
        );
    }

    /**
     * @test
     */
    public function returns_file_for_correct_path(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');
        $request->files->set('file', self::uploadedFile());

        $document = $extractor->extractFilesFromRequest($request, 'file');
        self::assertNotNull($document);
        self::assertInstanceOf(PendingFile::class, $document);
        self::assertSame("some content\n", $document->contents());
    }

    /**
     * @test
     */
    public function returns_file_for_correct_nested_path(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');
        $request->files->set('data', ['file' => self::uploadedFile()]);

        $document = $extractor->extractFilesFromRequest($request, 'data[file]');
        self::assertNotNull($document);
        self::assertInstanceOf(PendingFile::class, $document);
        self::assertSame("some content\n", $document->contents());
    }

    /**
     * @test
     */
    public function throws_for_single_file_with_array_of_files(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');
        $request->files->set('upload', [self::uploadedFile()]);

        $this->expectException(\LogicException::class);
        $extractor->extractFilesFromRequest($request, 'upload');
    }

    /**
     * @test
     */
    public function returns_empty_array_for_empty_request(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');

        self::assertSame(
            [],
            $extractor->extractFilesFromRequest($request, 'file', true)
        );
    }

    /**
     * @test
     */
    public function returns_empty_array_for_empty_path(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');
        $request->files->set('upload', [self::uploadedFile()]);

        self::assertSame(
            [],
            $extractor->extractFilesFromRequest($request, 'file', true)
        );
    }

    /**
     * @test
     */
    public function returns_array_for_single_file_path(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');
        $request->files->set('file', self::uploadedFile());

        $documents = $extractor->extractFilesFromRequest($request, 'file', true);
        self::assertIsArray($documents);
        self::assertCount(1, $documents);
        self::assertInstanceOf(PendingFile::class, $documents[0]);
        self::assertSame("some content\n", $documents[0]->contents());
    }

    /**
     * @test
     */
    public function returns_array_for_multiple_files_path(): void
    {
        $extractor = self::extractor();

        $request = Request::create('');
        $request->files->set('file', [self::uploadedFile(), self::uploadedFile()]);

        $documents = $extractor->extractFilesFromRequest($request, 'file', true);
        self::assertIsArray($documents);
        self::assertCount(2, $documents);
        self::assertInstanceOf(PendingFile::class, $documents[0]);
        self::assertSame("some content\n", $documents[0]->contents());
    }

    private static function uploadedFile(): UploadedFile
    {
        return new UploadedFile(
            __DIR__.'/../../../Fixtures/files/textfile.txt',
            'test.txt',
            test: true
        );
    }

    private static function extractor(): RequestFilesExtractor
    {
        return new RequestFilesExtractor(
            new PropertyAccessor(
                PropertyAccessor::DISALLOW_MAGIC_METHODS,
                PropertyAccessor::THROW_ON_INVALID_PROPERTY_PATH
            )
        );
    }
}

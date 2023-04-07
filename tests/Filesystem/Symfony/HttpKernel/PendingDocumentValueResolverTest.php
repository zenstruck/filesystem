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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class PendingDocumentValueResolverTest extends WebTestCase
{
    /**
     * @test
     */
    public function do_nothing_on_wrong_type(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            'no-injection',
            files: ['file' => self::uploadedFile()]
        );

        self::assertSame('0', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function inject_on_typed_argument(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            'single-file',
        );

        self::assertSame('', $client->getResponse()->getContent());

        $client->request(
            'GET',
            'single-file',
            files: ['file' => self::uploadedFile()]
        );

        self::assertSame("some content\n", $client->getResponse()->getContent());

        $client->request(
            'GET',
            'single-image',
            files: ['image' => self::uploadedImage()]
        );

        self::assertSame('563', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function inject_stored(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            'single-stored-file',
            files: ['file' => self::uploadedFile()]
        );

        self::assertSame(
            "public://eb9c2bf0eb63f3a7bc0ea37ef18aeba5/test.txt:some content\n",
            $client->getResponse()->getContent()
        );
    }

    /**
     * @test
     */
    public function inject_on_typed_argument_with_path(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            'single-file-with-path',
            files: ['data' => ['file' => self::uploadedFile()]]
        );

        self::assertSame("some content\n", $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function inject_array_on_argument_with_attribute(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            'multiple-files'
        );

        self::assertSame('0', $client->getResponse()->getContent());

        $client->request(
            'GET',
            'multiple-files',
            files: ['files' => self::uploadedFile()]
        );

        self::assertSame('1', $client->getResponse()->getContent());

        $client->request(
            'GET',
            'multiple-images',
            files: ['images' => self::uploadedImage()]
        );

        self::assertSame('1', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function inject_array_on_argument_with_attribute_and_path(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            'multiple-files-with-path'
        );

        self::assertSame('0', $client->getResponse()->getContent());

        $client->request(
            'GET',
            'multiple-files-with-path',
            files: ['data' => ['files' => self::uploadedFile()]]
        );

        self::assertSame('1', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function returns_exception_for_invalid_file(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            'single-image',
            files: ['image' => self::uploadedFile()]
        );
        $response = $client->getResponse();
        self::assertSame(422, $response->getStatusCode());

        if (\PHP_VERSION_ID >= 80100) {
            $client->request(
                'GET',
                'validated-file',
                files: ['file' => self::uploadedFile()]
            );
            $response = $client->getResponse();

            self::assertSame(422, $response->getStatusCode());
        }
    }

    private static function uploadedFile(): UploadedFile
    {
        return new UploadedFile(
            fixture('textfile.txt'),
            'test.txt',
            test: true
        );
    }

    private static function uploadedImage(): UploadedFile
    {
        return new UploadedFile(
            fixture('symfony.png'),
            'symfony.png',
            test: true
        );
    }
}

<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Symfony\Form;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFileTypeFunctionalTest extends WebTestCase
{
    /**
     * @test
     */
    public function can_upload_file(): void
    {
        $client = self::createClient();
        $client->request('POST', '/submit-form', files: ['file' => self::file('symfony.png')]);

        $this->assertSame(PendingFile::class, $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function can_upload_image(): void
    {
        $client = self::createClient();
        $client->request('POST', '/submit-form?image=true', files: ['file' => self::file('symfony.png')]);

        $this->assertSame(PendingImage::class, $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function can_upload_multiple_files(): void
    {
        $client = self::createClient();
        $client->request('POST', '/submit-form?multiple=true', files: ['file' => [
            self::file('symfony.png'),
            self::file('symfony.jpg'),
        ]]);

        $this->assertSame([PendingFile::class, PendingFile::class], \json_decode($client->getResponse()->getContent()));
    }

    /**
     * @test
     */
    public function can_upload_multiple_images(): void
    {
        $client = self::createClient();
        $client->request('POST', '/submit-form?multiple=true&image=true', files: ['file' => [
            self::file('symfony.png'),
            self::file('symfony.jpg'),
        ]]);

        $this->assertSame([PendingImage::class, PendingImage::class], \json_decode($client->getResponse()->getContent()));
    }

    private static function file(string $filename): UploadedFile
    {
        return new UploadedFile(fixture($filename), $filename, test: true);
    }
}

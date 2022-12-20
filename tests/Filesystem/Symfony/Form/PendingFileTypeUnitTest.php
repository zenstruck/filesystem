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

use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Node\File\Image\PendingImage;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Symfony\Form\PendingFileType;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PendingFileTypeUnitTest extends TypeTestCase
{
    /**
     * @test
     */
    public function set_data(): void
    {
        $form = $this->factory->create(PendingFileType::class);

        $data = new PendingFile(fixture('symfony.png'));

        $form->setData($data);

        $this->assertSame($data, $form->getData());
    }

    /**
     * @test
     */
    public function set_data_image(): void
    {
        $form = $this->factory->create(PendingFileType::class, options: ['image' => true]);

        $data = new PendingImage(fixture('symfony.png'));

        $form->setData($data);

        $this->assertSame($data, $form->getData());
    }

    /**
     * @test
     */
    public function set_data_multiple(): void
    {
        $form = $this->factory->create(PendingFileType::class, options: ['multiple' => true]);

        $data = [
            new PendingFile(fixture('symfony.png')),
            new PendingFile(fixture('symfony.png')),
        ];

        $form->setData($data);

        $this->assertSame($data, $form->getData());
    }

    /**
     * @test
     */
    public function set_data_multiple_image(): void
    {
        $form = $this->factory->create(PendingFileType::class, options: ['multiple' => true, 'image' => true]);

        $data = [
            new PendingImage(fixture('symfony.png')),
            new PendingImage(fixture('symfony.png')),
        ];

        $form->setData($data);

        $this->assertSame($data, $form->getData());
    }

    /**
     * @test
     */
    public function submit(): void
    {
        $form = $this->factory->createBuilder(PendingFileType::class)
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm()
        ;
        $data = new UploadedFile(fixture('symfony.png'), 'file1.txt', test: true);

        $form->submit($data);

        $this->assertSame(PendingFile::class, $form->getData()::class);
        $this->assertSame($data->getClientOriginalName(), $form->getData()->path()->name());
    }

    /**
     * @test
     */
    public function submit_image(): void
    {
        $form = $this->factory->createBuilder(PendingFileType::class, options: ['image' => true])
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm()
        ;
        $data = new UploadedFile(fixture('symfony.png'), 'file1.txt', test: true);

        $form->submit($data);

        $this->assertSame(PendingImage::class, $form->getData()::class);
        $this->assertSame($data->getClientOriginalName(), $form->getData()->path()->name());
    }

    /**
     * @test
     */
    public function submit_multiple(): void
    {
        $form = $this->factory->createBuilder(PendingFileType::class, options: ['multiple' => true])
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm()
        ;
        $data = [
            new UploadedFile(fixture('symfony.png'), 'file1.txt', test: true),
            new UploadedFile(fixture('symfony.png'), 'file2.txt', test: true),
        ];

        $form->submit($data);

        $this->assertIsArray($form->getData());
        $this->assertCount(2, $form->getData());
        $this->assertSame(PendingFile::class, $form->getData()[0]::class);
        $this->assertSame(PendingFile::class, $form->getData()[1]::class);
        $this->assertSame($data[0]->getClientOriginalName(), $form->getData()[0]->path()->name());
        $this->assertSame($data[1]->getClientOriginalName(), $form->getData()[1]->path()->name());
    }

    /**
     * @test
     */
    public function submit_multiple_image(): void
    {
        $form = $this->factory->createBuilder(PendingFileType::class, options: ['multiple' => true, 'image' => true])
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm()
        ;
        $data = [
            new UploadedFile(fixture('symfony.png'), 'file1.txt', test: true),
            new UploadedFile(fixture('symfony.png'), 'file2.txt', test: true),
        ];

        $form->submit($data);

        $this->assertIsArray($form->getData());
        $this->assertCount(2, $form->getData());
        $this->assertSame(PendingImage::class, $form->getData()[0]::class);
        $this->assertSame(PendingImage::class, $form->getData()[1]::class);
        $this->assertSame($data[0]->getClientOriginalName(), $form->getData()[0]->path()->name());
        $this->assertSame($data[1]->getClientOriginalName(), $form->getData()[1]->path()->name());
    }

    /**
     * @test
     */
    public function submit_null(): void
    {
        $form = $this->factory->createBuilder(PendingFileType::class)
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm()
        ;

        $form->submit(null);

        $this->assertNull($form->getData());
    }

    /**
     * @test
     */
    public function submit_multi_null(): void
    {
        $form = $this->factory->createBuilder(PendingFileType::class, options: ['multiple' => true])
            ->setRequestHandler(new HttpFoundationRequestHandler())
            ->getForm()
        ;

        $form->submit(null);

        $this->assertIsArray($form->getData());
        $this->assertEmpty($form->getData());
    }
}

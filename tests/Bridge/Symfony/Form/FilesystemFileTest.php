<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Symfony\Form;

use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Filesystem\Bridge\Symfony\Form\Type\FilesystemFileType;
use Zenstruck\Filesystem\Node\File\PendingFile;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class FilesystemFileTest extends TypeTestCase
{
    private function testFile(): File
    {
        return new File(__DIR__.'/../../../Fixture/files/symfony.png');
    }

    private function uploadedFile(): UploadedFile
    {
        return new UploadedFile(__DIR__.'/../../../Fixture/files/symfony.png', 'symfony.png', null, 0, true);
    }

    public function testSetData(): void {
        $form = $this->factory->create(FilesystemFileType::class);

        $data = new PendingFile($this->testFile());

        $form->setData($data);

        self::assertSame($data, $form->getData());
    }

    public function testSubmit(): void {
        $requestHandler = new HttpFoundationRequestHandler();
        $form = $this->factory->createBuilder(FilesystemFileType::class)->setRequestHandler($requestHandler)->getForm();
        $data = $this->uploadedFile();

        $form->submit($data);

        self::assertInstanceOf(PendingFile::class, $form->getData());
        $this->assertSame($data->getClientOriginalName(), $form->getData()->originalName());
    }
}

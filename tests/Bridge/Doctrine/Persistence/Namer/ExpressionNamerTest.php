<?php

namespace Zenstruck\Filesystem\Tests\Bridge\Doctrine\Persistence\Namer;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Filesystem\Bridge\Doctrine\Persistence\Namer\ExpressionNamer;
use Zenstruck\Filesystem\Node\File\PendingFile;
use Zenstruck\Filesystem\Tests\FilesystemTest;
use Zenstruck\Filesystem\Tests\Fixture\Symfony\Factory\Entity1Factory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExpressionNamerTest extends KernelTestCase
{
    use Factories, ResetDatabase;

    /**
     * @test
     * @dataProvider expressionProvider
     */
    public function generate_name($expression, PendingFile $file, $expected): void
    {
        $object = Entity1Factory::createOne()->object();

        $this->assertSame($expected, self::namer()->generateName($file, $object, ['expression' => $expression]));
    }

    public static function expressionProvider(): iterable
    {
        $file = new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg');

        yield ['foo/{name}{ext}', $file, 'foo/some-crazy-file.png'];
        yield ['foo/{checksum}-{name}{ext}', $file, 'foo/f75b8179e4bbe7e2b4a074dcef62de95-some-crazy-file.png'];
        yield ['foo/{objectId}-{name}{ext}', $file, 'foo/1-some-crazy-file.png'];
    }

    /**
     * @test
     */
    public function can_generate_with_random_string(): void
    {
        $name1 = self::namer()->generateName(
            new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg'),
            new \stdClass(),
            ['expression' => '{rand}-{rand}']
        );

        $name2 = self::namer()->generateName(
            new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg'),
            new \stdClass(),
            ['expression' => '{rand}-{rand}']
        );

        $this->assertMatchesRegularExpression('#^[0-9a-z]{6}-[0-9a-z]{6}#', $name1);
        $this->assertNotSame($name1, $name2);
        $this->assertSame(13, \mb_strlen($name1));
    }

    /**
     * @test
     */
    public function default_expression(): void
    {
        $object = Entity1Factory::createOne()->object();
        $name = self::namer()->generateName(
            new PendingFile(FilesystemTest::FIXTURE_DIR.'/some CRazy file.pNg'),
            $object,
        );

        $this->assertMatchesRegularExpression('#some-crazy-file-1-[0-9a-z]{6}\.png#', $name);
    }

    private static function namer(): ExpressionNamer
    {
        return new ExpressionNamer(self::getContainer()->get('doctrine'));
    }
}

<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Archive;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Archive\TarFile;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TarFileTest extends TestCase
{
    /**
     * @test
     * @dataProvider tarFileProvider
     */
    public function can_read_different_types_of_tar_files($filename): void
    {
        $archive = new TarFile($filename);

        $this->assertSame('phar://'.$filename, $archive->name());
        $this->assertCount(2, $archive->directory());
        $this->assertCount(1, $archive->directory()->files());
        $this->assertCount(1, $archive->directory()->directories());
        $this->assertCount(3, $archive->directory()->recursive());
        $this->assertSame('contents 1', $archive->directory()->files()->first()->ensureFile()->contents());
    }

    public static function tarFileProvider(): iterable
    {
        yield [fixture('archive.tar')];
        yield [fixture('archive.tar.gz')];
        yield [fixture('archive.tar.bz2')];
    }
}

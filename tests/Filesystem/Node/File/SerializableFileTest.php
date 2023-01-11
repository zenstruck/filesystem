<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Tests\Filesystem\Node\File;

use PHPUnit\Framework\TestCase;
use Zenstruck\Filesystem\Node\File;
use Zenstruck\Filesystem\Node\File\SerializableFile;
use Zenstruck\Tests\Filesystem\Node\FileTests;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class SerializableFileTest extends TestCase
{
    use FileTests;

    /**
     * @test
     */
    public function can_serialize(): void
    {
        $this->markTestIncomplete();
    }

    protected function createFile(\SplFileInfo $file, string $path): File
    {
        return new SerializableFile($this->filesystem->write($path, $file)->last()->ensureFile(), []);
    }
}

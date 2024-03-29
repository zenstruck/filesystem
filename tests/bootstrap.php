<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use League\Flysystem\Config;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UrlGeneration\PrefixPublicUrlGenerator;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\Flysystem\TransformUrlGenerator;
use Zenstruck\Filesystem\FlysystemFilesystem;

require_once __DIR__.'/../vendor/autoload.php';

const FIXTURE_DIR = __DIR__.'/Fixtures/files';
const TEMP_DIR = __DIR__.'/../var/temp';

(new Symfony\Component\Filesystem\Filesystem())->remove(\dirname(TEMP_DIR));
(new Symfony\Component\Filesystem\Filesystem())->mkdir(TEMP_DIR);

function fixture(string $name): SplFileInfo
{
    return new SplFileInfo(FIXTURE_DIR.'/'.$name);
}

function tempfile(string $name): SplFileInfo
{
    return new SplFileInfo(TEMP_DIR.'/'.$name);
}

function fixture_filesystem(): Filesystem
{
    return new FlysystemFilesystem(new ReadOnlyFilesystemAdapter(new LocalFilesystemAdapter(FIXTURE_DIR)));
}

function temp_filesystem(): Filesystem
{
    return new FlysystemFilesystem(TEMP_DIR);
}

function in_memory_filesystem(string $name = 'default'): Filesystem
{
    return new FlysystemFilesystem(
        new InMemoryFilesystemAdapter(),
        name: $name,
        features: [
            PublicUrlGenerator::class => new PrefixPublicUrlGenerator('/prefix'),
            TemporaryUrlGenerator::class => new class() implements TemporaryUrlGenerator {
                public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
                {
                    return "/temp/{$path}?expires={$expiresAt->getTimestamp()}";
                }
            },
            TransformUrlGenerator::class => new class() implements TransformUrlGenerator {
                public function transformUrl(string $path, array|string $filter, Config $config): string
                {
                    return "/generate/{$path}?filter={$filter}";
                }
            },
        ],
    );
}

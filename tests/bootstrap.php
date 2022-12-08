<?php

use League\Flysystem\Config;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;

require_once __DIR__.'/../vendor/autoload.php';

const FIXTURE_DIR = __DIR__.'/Fixtures';

function fixture_file(string $name): SplFileInfo
{
    return new \SplFileInfo(FIXTURE_DIR.'/'.$name);
}

function in_memory_filesystem(): Filesystem
{
    return new FlysystemFilesystem(new Flysystem(
        new InMemoryFilesystemAdapter(),
        ['public_url' => '/prefix'],
        temporaryUrlGenerator: new class() implements TemporaryUrlGenerator {
            public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
            {
                return "/temp/{$path}?expires={$expiresAt->getTimestamp()}";
            }
        }
    ));
}

<?php

use League\Flysystem\Config;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FlysystemFilesystem;

require_once __DIR__.'/../vendor/autoload.php';

const FIXTURE_DIR = __DIR__.'/Fixtures';

function fixture(string $name): SplFileInfo
{
    return new \SplFileInfo(FIXTURE_DIR.'/'.$name);
}

function fixture_filesystem(): Filesystem
{
    return new FlysystemFilesystem(new Flysystem(
        new ReadOnlyFilesystemAdapter(
            new LocalFilesystemAdapter(FIXTURE_DIR)
        )
    ));
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

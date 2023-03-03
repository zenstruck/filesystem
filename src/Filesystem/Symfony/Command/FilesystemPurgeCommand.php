<?php

/*
 * This file is part of the zenstruck/filesystem package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Filesystem\Symfony\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\Filesystem;
use Zenstruck\Filesystem\FilesystemRegistry;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsCommand('zenstruck:filesystem:purge', 'Purge files from a filesystem based on a filter')]
final class FilesystemPurgeCommand extends Command
{
    public function __construct(private FilesystemRegistry $filesystems)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filesystem', InputArgument::REQUIRED, 'The filesystem name')
            ->addArgument('directory', InputArgument::OPTIONAL, 'The directory', '')
            ->addOption('older-than', mode: InputOption::VALUE_REQUIRED, description: 'Timestamp (ie "2023-01-01") or interval (ie "30 days")')
            ->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Recursively purge directory')
            ->addOption('remove-empty-directories', mode: InputOption::VALUE_NONE, description: 'After purging, remove any empty directories')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = $this->filesystems->get($input->getArgument('filesystem'));

        $io = new SymfonyStyle($input, $output);
        $olderThan = self::parseOlderThan($input->getOption('older-than'));
        $directory = $filesystem->directory($input->getArgument('directory'));
        $count = 0;

        if ($input->getOption('recursive')) {
            $directory = $directory->recursive();
        }

        $filteredDirectory = $directory->files();

        if ($olderThan) {
            $filteredDirectory = $filteredDirectory->olderThan($olderThan);
        }

        $io->title(\sprintf('Purging Files in Filesystem "%s"', $filesystem->name()));

        if ($directory->path()->toString()) {
            $io->comment(\sprintf('In directory "%s"', $directory->path()));
        }

        if (!$io->isVerbose()) {
            $io->progressStart();
        }

        foreach ($filteredDirectory as $file) {
            $filesystem->delete($file->path());
            ++$count;
            $io->isVerbose() ? $io->comment('[Deleted] '.$file->path()) : $io->progressAdvance();
        }

        if (!$io->isVerbose()) {
            $io->progressFinish();
        }

        $io->success(\sprintf('Deleted %d files.', $count));

        if (!$input->getOption('remove-empty-directories')) {
            return self::SUCCESS;
        }

        $toDelete = [];

        $io->section('Removing Empty Directories');

        if (!$io->isVerbose()) {
            $io->progressStart();
        }

        foreach ($directory->directories() as $directory) {
            if (!self::isEmpty($directory)) {
                continue;
            }

            $toDelete[] = $directory->path();
            $io->isVerbose() ?: $io->progressAdvance();
        }

        foreach ($toDelete as $path) {
            $filesystem->delete($path);
            $io->isVerbose() ? $io->comment('[Deleted] '.$path) : $io->progressAdvance();
        }

        if (!$io->isVerbose()) {
            $io->progressFinish();
        }

        $io->success(\sprintf('Deleted %d empty directories.', \count($toDelete)));

        return self::SUCCESS;
    }

    private static function parseOlderThan(?string $value): ?\DateTimeInterface
    {
        if (!$value) {
            return null;
        }

        $interval = \is_numeric($value) ? new \DateInterval("PT{$value}S") : \DateInterval::createFromDateString($value);
        $timestamp = new \DateTimeImmutable();

        if ($interval && $timestamp->getTimestamp() !== ($timestamp = $timestamp->sub($interval))->getTimestamp()) {
            return $timestamp;
        }

        return new \DateTimeImmutable($value);
    }

    private static function isEmpty(Filesystem\Node\Directory $directory): bool
    {
        foreach ($directory as $node) {
            return false;
        }

        return true;
    }
}

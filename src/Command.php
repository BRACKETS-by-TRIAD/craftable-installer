<?php

namespace Brackets\CraftableInstaller\Console;

use Symfony\Component\Console\Command\Command as ParentCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class Command extends ParentCommand
{
    const LARAVEL_CURRENT_LATEST_VERSION = '9.*';
    const LARAVEL_CURRENT_LATEST_VERSION_DEV = '9.*';

    protected $output;

    /**
     * @param OutputInterface $output
     * @param array $commands
     */
    protected function runCommand($command)
    {
        if (method_exists(Process::class, 'fromShellCommandline')) {
            $process = Process::fromShellCommandline($command, null, null, null, null);
        } else {
            $process = new Process($command, null, null, null, null);
        }

        $process->setTty(Process::isTtySupported());

        return $process->run();
    }
}
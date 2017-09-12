<?php

namespace Brackets\SimplewebInstaller\Console;

use Laravel\Installer\Console\NewCommand as LaravelNew;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LaravelNewCommand extends LaravelNew
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('laravel')
            ->setDescription('Create a new Laravel application.')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forces install even if the directory already exists');
    }
}

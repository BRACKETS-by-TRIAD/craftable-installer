<?php

namespace Brackets\CraftableInstaller\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class NewCommand extends Command
{
    const LARAVEL_CURRENT_LTS_VERSION = '6.*';
    const LARAVEL_CURRENT_LATEST_VERSION = '6.*';

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Craftable application using latest Laravel (currently ' . self::LARAVEL_CURRENT_LATEST_VERSION . ').')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE,
                'Installs the latest DEV release ready for Craftable development')
            ->addOption('lts', null, InputOption::VALUE_NONE,
                'Installs Craftable using LTS release of Laravel (currently ' . self::LARAVEL_CURRENT_LTS_VERSION . ')')
            ->addOption('no-install', null, InputOption::VALUE_NONE, 'Do not run craftable:install');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->findComposer();

        $commands = [];

        $directory = '"' . $input->getArgument('name') . '"';

        $commands[] = $composer . ' create-project --prefer-dist laravel/laravel ' . $directory . ($input->getOption('lts') ? ' "' . self::LARAVEL_CURRENT_LTS_VERSION . '" ' : ' "' . self::LARAVEL_CURRENT_LATEST_VERSION . '" ');

        $commands[] = 'cd ' . $directory;

        $output->writeln('<info>Crafting Craftable :) ...</info>');

        $packages = [
            'brackets/admin-ui',
            'brackets/admin-listing',
            'brackets/admin-auth',
            'brackets/admin-translations',
            'brackets/media',
            'brackets/translatable',
            'brackets/advanced-logger',
            'brackets/craftable',
        ];

        if ($input->getOption('dev')) {
            $packages = array_map(static function ($package) {
                return '"' . $package . ':dev-master"';
            }, $packages);
            $commands[] = $composer . ' require ' . implode(' ', $packages);
            $commands[] = $composer . ' require --dev "brackets/admin-generator:dev-master"';
            $commands[] = 'rm -rf vendor/brackets';
            $commands[] = $composer . ' update --prefer-source';
        } else {
            $commands[] = $composer . ' require "brackets/craftable"';
            $commands[] = $composer . ' require --dev "brackets/admin-generator"';
        }

        if (!$input->getOption('no-install')) {
            // FIXME these commands seem to not work on some environments (probably on some Windows platforms) when run this way (but they work when run manually) - this needs further investigation
            $commands[] = '"' . PHP_BINARY . '" artisan craftable:init-env';
            $commands[] = '"' . PHP_BINARY . '" artisan craftable:install';
            $commands[] = 'npm install';
            $commands[] = 'npm run dev';

        }

        // TODO it would be better to run not all commands in once, because some of them may fail
        $process = new Process(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(static function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>Craftable crafted! Craft something crafty ;)</comment>');

        return 0;
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }
}
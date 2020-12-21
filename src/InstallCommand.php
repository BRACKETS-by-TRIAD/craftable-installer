<?php

namespace Brackets\CraftableInstaller\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    private $sailAvailable = true;

    private $sailIsUp = false;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install Craftable application latest Laravel (currently ' . self::LARAVEL_CURRENT_LATEST_VERSION . ').')
            ->addOption('dev', null, InputOption::VALUE_NONE,
                'Installs the latest DEV release ready for Craftable development')
            ->addOption('no-sail', null, InputOption::VALUE_NONE, 'Do not use Laravel Sail. Expects composer, php, database and npm available on your system.');
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
        $this->output = $output;

        $this->sailAvailable = !boolval($input->getOption('no-sail'));

        if ($this->sailAvailable) {
            $this->checkIfSailIsUp();
        }

        $output->writeln('<info>Crafting Craftable :) ...</info>');

        // FIXME test DB connection, maybe just try to run "artisan migrate"
        $output->writeln('First testing database connection...');
        $status = $this->runCommand($this->findArtisan() . ' migrate:status');

        if (intval($status) > 0) {
            $output->writeln('<error>...cannot connect to database. Check your .env settings. Aborting.</error>');
            return 1;
        }
        $output->writeln('...database connection OK');

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

        $commands = [];

        if ($input->getOption('dev')) {
            $packages = array_map(static function ($package) {
                return '"' . $package . ':dev-master"';
            }, $packages);
            $commands[] = $this->findComposer() . ' require --prefer-source ' . implode(' ', $packages);
            $commands[] = $this->findComposer() . ' require --prefer-source --dev "brackets/admin-generator:dev-master"';
        } else {
            // FIXME remove --ignore-platform-reqs

            // FIXME allow switch to PostgreSQL when using Sail

            // FIXME problem with default .env DB_HOST, it differs when installing Laravel using the primary way

            $commands[] = $this->findComposer() . ' require "brackets/craftable" --ignore-platform-reqs';
            $commands[] = $this->findComposer() . ' require --dev "brackets/admin-generator" --ignore-platform-reqs';
        }

        $this->runCommand(implode(' && ', $commands));

        $this->runCommand($this->findArtisan() . ' craftable:install');

        $this->runCommand($this->findNpm() . ' install');

        $this->runCommand($this->findNpm() . ' run dev');

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
        if ($this->sailAvailable && file_exists($this->findSail())) {
            $this->checkIfSailIsUp();
            return $this->findSail() . ' composer';
        } else {
            return 'composer';
        }
    }

    /**
     * Get the PHP
     *
     * @return string
     */
    protected function findArtisan()
    {
        if ($this->sailAvailable) {
            $this->checkIfSailIsUp();
            return $this->findSail() . ' artisan';
        }

        return PHP_BINARY . ' artisan';
    }

    /**
     * Get the NPM
     *
     * @return string
     */
    protected function findNpm()
    {
        if ($this->sailAvailable) {
            $this->checkIfSailIsUp();
            return $this->findSail() .' npm';
        }

        return 'npm';
    }

    private function checkIfSailIsUp()
    {
        if (!$this->sailIsUp) {
            $this->sailIsUp = true;
            $this->runCommand($this->findSail() . ' up -d');
        }
    }

    private function findSail()
    {
        return '.' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'sail';
    }
}
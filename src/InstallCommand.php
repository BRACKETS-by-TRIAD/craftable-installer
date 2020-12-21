<?php

namespace Brackets\CraftableInstaller\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    private $useSail = true;

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

        $this->useSail = !boolval($input->getOption('no-sail'));

        if ($this->useSail) {

            // if we use sail, then we try to install, never-mind we are doing it every time
            $response = $this->runCommand($this->findArtisan() . ' sail:install');
            if (intval($response) > 0) {
                $output->writeln('<error>Aborted.</error>');
                return 1;
            }

            // then if it is running, then nevermind, but if not, then start it
            $this->checkIfSailIsUp();
        }

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

            $commands[] = $this->findComposer() . ' require "brackets/craftable" --ignore-platform-reqs';
            $commands[] = $this->findComposer() . ' require --dev "brackets/admin-generator" --ignore-platform-reqs';
        }

        $this->runCommand(implode(' && ', $commands));

        $response = $this->runCommand($this->findArtisan() . ' craftable:test-db-connection');
        if (intval($response) > 0) {
            $output->writeln('<error>Aborted.</error>');
            return 1;
        }

        $response = $this->runCommand($this->findArtisan() . ' craftable:install');
        if (intval($response) > 0) {
            $output->writeln('<error>Aborted.</error>');
            return 1;
        }

        $response = $this->runCommand($this->findNpm() . ' install');
        if (intval($response) > 0) {
            $output->writeln('<error>Aborted.</error>');
            return 1;
        }

        $response = $this->runCommand($this->findNpm() . ' run dev');
        if (intval($response) > 0) {
            $output->writeln('<error>Aborted.</error>');
            return 1;
        }

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
        if ($this->useSail && file_exists($this->findSail())) {
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
        if ($this->useSail) {
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
        if ($this->useSail) {
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
        return '.' . DIRECTORY_SEPARATOR. 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'sail';
    }
}
<?php

namespace Brackets\CraftableInstaller\Console;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Craftable application.')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest DEV release')
            ->addOption('no-install', null, InputOption::VALUE_NONE, 'Do not run craftable:install')
            ;
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $command = $this->getApplication()->find('laravel');

        $arguments = array(
            'name'    => $input->getArgument('name'),
        );

        $greetInput = new ArrayInput($arguments);
        $returnCode = $command->run($greetInput, $output);

        // TODO check $returnCode and continue only if no error has occured

        $directory = ($input->getArgument('name'));

        $output->writeln('<info>Crafting Craftable :) ...</info>');

        $composer = $this->findComposer();

        $commands = [];

        $packages = [
            "brackets/admin-ui",
            "brackets/admin-listing",
            "brackets/admin-auth",
            "brackets/admin-translations",
            "brackets/media",
            "brackets/translatable",
            "brackets/craftable",
        ];

        if ($input->getOption('dev')) {
            $packages = array_map(function($package) {
                return '"'.$package.':dev-master"';
            }, $packages);
            array_push($commands, $composer.' require '.implode(' ', $packages));
            array_push($commands, $composer.' require --dev "brackets/admin-generator:dev-master"');
            array_push($commands, 'rm -rf vendor/brackets');
            array_push($commands, $composer.' update --prefer-source');
        } else {
            array_push($commands, $composer.' require "brackets/craftable"');
            array_push($commands, $composer.' require --dev "brackets/admin-generator"');
        }
        if (!$input->getOption('no-install')) {
            array_push($commands, '"'.PHP_BINARY.'" artisan craftable:init-env');
            array_push($commands, '"'.PHP_BINARY.'" artisan craftable:install');
            array_push($commands, 'npm install');
            array_push($commands, 'npm run dev');

        }


        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>Craftable crafted! Craft something crafty ;)</comment>');
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
    }
}
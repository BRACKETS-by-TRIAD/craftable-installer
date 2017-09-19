<?php

namespace Brackets\SimplewebInstaller\Console;

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
            ->setDescription('Create a new SimpleWEB application.')
            ->addArgument('name', InputArgument::OPTIONAL)
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

        $output->writeln('<info>Crafting SimpleWEB...</info>');

        $composer = $this->findComposer();

        $commands = [];

        array_push($commands,$composer.' require "brackets/simpleweb"');
        array_push($commands,$composer.' require --dev "brackets/admin-generator"');

        $process = new Process(implode(' && ', $commands), $directory, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>SimpleWEB ready! Build something amazing.</comment>');
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
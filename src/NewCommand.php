<?php

namespace Brackets\CraftableInstaller\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    /** @var bool */
    private $useGlobalComposer = null;

    /**
     * @var string
     */
    private $directory;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        // FIXME "use-docker" probably does not have any sense, as long as craftable-installer is itself a composer package
        $this
            ->setName('new')
            ->setDescription('Create a new Craftable-compatible Laravel application (currently ' . self::LARAVEL_CURRENT_LATEST_VERSION . ').')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE,
                'Installs the latest DEV release ready for Craftable development')
            ->addOption('use-docker', null, InputOption::VALUE_NONE, 'Use small Docker container instead of global composer');
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

        $this->directory = $input->getArgument('name');

        $this->useGlobalComposer = !boolval($input->getOption('use-docker'));

        if ($input->getOption('dev')) {
            $this->runCommand($this->findComposer() . ' create-project --prefer-dist laravel/laravel "' . $this->directory . ( '" "' . self::LARAVEL_CURRENT_LATEST_VERSION_DEV . '"'));
        } else {
            $this->runCommand($this->findComposer() . ' create-project --prefer-dist laravel/laravel "' . $this->directory . ( '" "' . self::LARAVEL_CURRENT_LATEST_VERSION . '"'));
        }

        $this->runCommand('cd ' . $this->directory . ' && ' . $this->findComposer() . ' require psr/simple-cache:^1.0');

        return 0;
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if ($this->useGlobalComposer) {
            return 'composer';
        } else {
            return 'docker run --rm \
                        -v $(pwd):/opt \
                        -w /opt \
                        laravelsail/php80-composer:latest \
                        composer';
        }
    }
}
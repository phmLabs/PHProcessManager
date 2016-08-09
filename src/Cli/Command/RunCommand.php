<?php

namespace phmLabs\ProcessManager\Cli\Command;

use phmLabs\ProcessManager\Daemon\Daemon;
use phmLabs\ProcessManager\Process\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends PhpmCommand
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Start the PHProcess Manager daemon')
            ->addOption('lockFile', 'l', InputOption::VALUE_OPTIONAL, 'The lockFile.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initQueue();

        $deamon = new Daemon($this->queue, new Manager(), $input->getOption('lockFile'));
        $output->writeln("\n  <info>PHProcess Manager</info> running ... \n");
        $deamon->run();
        $output->writeln("  PHProcess killed.\n\n");
    }
}

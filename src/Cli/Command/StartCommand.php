<?php

namespace phmLabs\ProcessManager\Cli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends PhpmCommand
{
    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('Start a process')
            ->addArgument('exec', InputArgument::REQUIRED, 'The command line command')
            ->addOption('appname', 'a', InputOption::VALUE_OPTIONAL, 'The application name.');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initQueue();

        $this->assertDaemonRunning($output);

        $commandLine = $input->getArgument('exec');

        if ($input->getOption('appname')) {
            $name = $input->getOption('appname');
        } else {
            $name = null;
        }

        $message = new \phmLabs\ProcessManager\Message\Command\Start($commandLine, $name);
        $response = $this->queue->sendMessage($message, true);

        $output->writeln("\n  <info>" . $response->getMessage() . "</info>\n");
    }
}
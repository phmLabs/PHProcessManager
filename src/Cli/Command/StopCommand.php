<?php

namespace phmLabs\ProcessManager\Cli\Command;

use phmLabs\ProcessManager\Message\Command\Stop;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StopCommand extends PhpmCommand
{
    protected function configure()
    {
        $this
            ->setName('stop')
            ->setDescription('Stop a process')
            ->addArgument('pid', InputArgument::REQUIRED, 'The process id (PID).');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initQueue();
        $this->assertDaemonRunning($output);

        $message = new Stop($input->getArgument('pid'));

        $response = $this->queue->sendMessage($message, true);

        $output->writeln("\n  " . $response->getMessage() . "\n\n");
    }
}
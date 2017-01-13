<?php

namespace phmLabs\ProcessManager\Cli\Command;

use phmLabs\ProcessManager\Message\Command\CloneCommand as cCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CloneCommand extends PhpmCommand
{
    protected function configure()
    {
        $this
            ->setName('clone')
            ->setDescription('Clone an existing process')
            ->addArgument('pid', InputArgument::REQUIRED, 'The process pid to clone');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initQueue();
        $this->assertDaemonRunning($output);

        $message = new cCommand($input->getArgument('pid'));

        $this->renderResponse($this->queue->sendMessage($message, true), $output);
    }
}
<?php

namespace phmLabs\ProcessManager\Cli\Command;

use phmLabs\ProcessManager\Message\Kill;
use phmLabs\ProcessManager\Process\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KillCommand extends PhpmCommand
{
    protected function configure()
    {
        $this
            ->setName('kill')
            ->setDescription('Kill PHProcess Manager daemon');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initQueue();
        $this->assertDaemonRunning($output);

        $message = new Kill();
        $response = $this->queue->sendMessage($message, true);

        if ($response->getMessage() == true) {
            $output->writeln("\n  <info>PHProcess Manager deamon successfully killed.</info>\n");
        } else {
            $output->writeln("\n  <error>Unable to kill PHProcess Manager deamon.</error>\n");
        }
    }
}

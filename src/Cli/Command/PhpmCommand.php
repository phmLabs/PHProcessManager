<?php

namespace phmLabs\ProcessManager\Cli\Command;

use Cilex\Provider\Console\Command;
use phmLabs\ProcessManager\Message\Response;
use phmLabs\ProcessManager\Queue\Queue;
use Symfony\Component\Console\Output\OutputInterface;

abstract class PhpmCommand extends Command
{
    const QUEUE_NAME = 8081979;

    /**
     * @var Queue
     */
    protected $queue;

    protected function initQueue()
    {
        $this->queue = new Queue(self::QUEUE_NAME);
    }

    protected function renderResponse(Response $response, OutputInterface $output)
    {
        if ($response->getStatus() == Response::STATUS_SUCCESS) {
            $output->writeln("\n  <info>" . $response->getMessage() . "</info>\n");
        } else {
            $output->writeln("\n  <error> " . $response->getMessage() . " </error>\n");
        }
    }

    protected function assertDaemonRunning(OutputInterface $output)
    {
        if (!$this->queue->discover()) {
            $output->writeln("\n   PHProcess Manager not running. Please run the daemon 'php phpm.phar run'\n");
            exit(1);
        }
    }
}
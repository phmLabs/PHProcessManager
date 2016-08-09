<?php

namespace phmLabs\ProcessManager\Cli\Command;

use phmLabs\ProcessManager\Message\Command\Info;
use phmLabs\ProcessManager\Process\Manager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Text\Table\Table;

class InfoCommand extends PhpmCommand
{
    protected function configure()
    {
        $this
            ->setName('info')
            ->setDescription('Show all running processes');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initQueue();
        $this->assertDaemonRunning($output);

        $message = new Info();
        $response = $this->queue->sendMessage($message, true);

        $processes = $response->getMessage();

        if (!is_array($processes)) {
            $output->writeln("\n  " . $processes . "\n\n");
        } else {
            $table = new Table(['padding' => 1, 'columnWidths' => [15, 7, 60, 9, 21]]);
            $table->appendRow(['app name', 'pid', 'command', 'restart', 'started']);

            foreach ($response->getMessage() as $name => $row) {
                if ($name == $row['pid']) {
                    $name = "<none>";
                }

                $table->appendRow([
                    (string)$name,
                    (string)$row['pid'],
                    (string)$row['command'],
                    (string)$row['restartCount'],
                    (string)$row['start']]);
            }
            $output->writeln("");
            $output->writeln($table->render());
            $output->writeln("");
        }
    }
}

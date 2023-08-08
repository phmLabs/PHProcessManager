<?php

namespace phmLabs\ProcessManager\Cli\Command;

use Laminas\Text\Table\Table;
use phmLabs\ProcessManager\Message\Command\Info;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

        $processes = json_decode($response->getMessage(), true);

        if (!is_array($processes)) {
            $output->writeln("\n  " . $processes . "\n\n");
        } else {
            $table = new Table(['padding' => 1, 'columnWidths' => [15, 7, 60, 9, 21]]);
            $table->appendRow(['app name', 'pid', 'command', 'restart', 'started']);

            foreach ($processes as $name => $row) {
                if ($row['name'] == $row['pid']) {
                    $name = "<none>";
                } else {
                    $name = $row['name'];
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

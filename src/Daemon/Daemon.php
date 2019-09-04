<?php

namespace phmLabs\ProcessManager\Daemon;

use phmLabs\ProcessManager\Message\Command\CloneCommand;
use phmLabs\ProcessManager\Message\Command\Info;
use phmLabs\ProcessManager\Message\Command\Start;
use phmLabs\ProcessManager\Message\Command\Stop;
use phmLabs\ProcessManager\Message\Discover;
use phmLabs\ProcessManager\Message\Kill;
use phmLabs\ProcessManager\Message\Message;
use phmLabs\ProcessManager\Message\Response;
use phmLabs\ProcessManager\Process\Manager;
use phmLabs\ProcessManager\Queue\Queue;

class Daemon
{
    private $queue;
    private $manager;
    private $stopRunning = false;
    private $interval = 500000;
    private $exportFile = '/tmp/phpm.lock';

    public function __construct(Queue $queue, Manager $manager, $exportFile = null)
    {
        $this->queue = $queue;
        $this->manager = $manager;

        if ($exportFile) {
            $this->exportFile = $exportFile;
        }

        $this->importProcessList();
    }

    public function run()
    {
        while (true) {

            $msg = $this->queue->receive();

            if ($msg) {
                $identifier = $msg['identifier'];
                $message = $msg['message'];
                $this->handleMessage($message, $identifier);
            }

            if ($this->stopRunning) {
                break;
            }

            $this->manager->restartDiedProcesses();

            usleep($this->interval);
        }

        $this->manager->killProcesses();
    }

    private function exportProcessList()
    {
        $processes = $this->manager->getProcesses();
        file_put_contents($this->exportFile, json_encode($processes, 10));
    }

    private function getCpuCount()
    {
        $numCpus = 1;
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $numCpus = count($matches[0]);
        } else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
            $process = @popen('wmic cpu get NumberOfCores', 'rb');
            if (false !== $process) {
                fgets($process);
                $numCpus = intval(fgets($process));
                pclose($process);
            }
        } else {
            $process = @popen('sysctl -a', 'rb');
            if (false !== $process) {
                $output = stream_get_contents($process);
                preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                if ($matches) {
                    $numCpus = intval($matches[1][0]);
                }
                pclose($process);
            }
        }
        return $numCpus;
    }

    private function getInstanceCount($process)
    {
        if ($process['instances']) {
            if ($process['instances'] == '%cpu_count%') {
                $cpuCount = $this->getCpuCount();
                $instances = $cpuCount - 1;
            } else {
                $instances = $process['instances'];
            }
        } else {
            $instances = 1;
        }

        return $instances;
    }

    private function importProcessList()
    {
        if (file_exists($this->exportFile)) {

            $processes = json_decode(file_get_contents($this->exportFile), true);

            if (!$processes) {
                throw new \RuntimeException('Seems like the given config file is invalid (' . $this->exportFile . ').');
            }

            foreach ($processes as $name => $process) {
                $instanceCount = $this->getInstanceCount($process);
                for ($i = 0; $i < $instanceCount; $i++) {
                    if ($i == 0) {
                        $processName = $name;
                    } else {
                        $processName = $name . '(' . ($i + 1) . ')';
                    }

                    $this->manager->start($process['command'], $processName);
                }
            }
        }
    }

    private function handleMessage(Message $message, $identifier)
    {
        if ($message instanceof Start) {
            try {
                $pid = $this->manager->start($message->getExecutable(), $message->getName());
                $responseMessage = 'Started command with pid ' . $pid . '.';
                $this->sendResponse(Response::STATUS_SUCCESS, $responseMessage, $identifier);
            } catch (\Exception $e) {
                $this->sendResponse(Response::STATUS_FAILURE, $e->getMessage(), $identifier);
            }
            $this->exportProcessList();
        } elseif ($message instanceof Info) {
            $processes = $this->manager->getProcesses();
            $this->sendResponse(Response::STATUS_SUCCESS, json_encode($processes), $identifier);
        } elseif ($message instanceof Stop) {
            if ($this->manager->stop($message->getPid())) {
                $this->sendResponse(Response::STATUS_SUCCESS, 'Process with pid ' . $message->getPid() . ' stopped successfully.', $identifier);
                $this->exportProcessList();
            } else {
                $this->sendResponse(Response::STATUS_FAILURE, 'Unable to stop process with pid ' . $message->getPid() . '.', $identifier);
            }
        } elseif ($message instanceof Discover) {
            $this->sendResponse(Response::STATUS_SUCCESS, true, $identifier);
        } elseif ($message instanceof Kill) {
            $this->sendResponse(Response::STATUS_SUCCESS, true, $identifier);
            $this->stopRunning = true;
        } elseif ($message instanceof CloneCommand) {
            try {
                $pid = $this->manager->cloneProcess($message->getPid());
                $this->sendResponse(Response::STATUS_SUCCESS, "Process successfully cloned. New pid is " . $pid . '.', $identifier);
            } catch (\Exception $e) {
                $this->sendResponse(Response::STATUS_FAILURE, $e->getMessage(), $identifier);
            }
            $this->exportProcessList();
        } else {
            $this->sendResponse(Response::STATUS_FAILURE, 'MassageType not found.', $identifier);
        }
    }

    private function sendResponse($status, $message, $identifier)
    {
        $response = new Response($status, $message, $identifier);
        $this->queue->sendResponse($response);
    }
}
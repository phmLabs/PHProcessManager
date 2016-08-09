<?php

namespace phmLabs\ProcessManager\Daemon;

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

    private function importProcessList()
    {
        if (file_exists($this->exportFile)) {
            $processes = json_decode(file_get_contents($this->exportFile), true);
            foreach ($processes as $name => $process) {
                $this->manager->start($process['command'], $name);
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
            $this->sendResponse(Response::STATUS_SUCCESS, $processes, $identifier);
        } elseif ($message instanceof Stop) {
            $this->manager->stop($message->getPid());
            $this->sendResponse(Response::STATUS_SUCCESS, 'Process stopped successfully.', $identifier);
            $this->exportProcessList();
        } elseif ($message instanceof Discover) {
            $this->sendResponse(Response::STATUS_SUCCESS, true, $identifier);
        } elseif ($message instanceof Kill) {
            $this->sendResponse(Response::STATUS_SUCCESS, true, $identifier);
            $this->stopRunning = true;
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
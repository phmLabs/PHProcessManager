<?php

namespace phmLabs\ProcessManager\Queue;

use phmLabs\ProcessManager\Message\Discover;
use phmLabs\ProcessManager\Message\Message;
use phmLabs\ProcessManager\Message\Response;

class Queue
{
    private $queue;
    private $identifer;
    private $sleepTime = 1000000;
    private $responseRetry = 3;

    public function __construct($identifier)
    {
        $this->queue = msg_get_queue($identifier);
        $this->identifer = $identifier;
    }

    public function discover()
    {
        $response = $this->sendMessage(new Discover(), true);
        return $response->getStatus() == Response::STATUS_SUCCESS;
    }

    public function receive($identifier = null, $removeQueue = false)
    {
        $queue = $this->getQueue($identifier);

        $msg_type = NULL;
        $msg = NULL;
        $max_msg_size = 100000;

        if (msg_receive($queue, 1, $msg_type, $max_msg_size, $msg, true, MSG_IPC_NOWAIT)) {
            if ($removeQueue && $identifier) {
                msg_remove_queue($queue);
            }

            return $msg;
        } else {
            return false;
        }
    }

    public function sendResponse(Response $response)
    {
        $message = $this->sendMessage($response, false, $response->getIdentifier());
        return $message;
    }

    private function getQueue($identifier = null)
    {
        if (!$identifier) {
            $queue = $this->queue;
        } else {
            $queue = msg_get_queue($identifier);
        }

        return $queue;
    }

    /**
     * @param Message $message
     * @param bool $awaitsResponse
     * @param integer $queueIdentifier
     * @return Response
     */
    public function sendMessage(Message $message, $awaitsResponse = false, $queueIdentifier = null)
    {
        $identifier = rand(0, 1000000000);
        $messageContainer = ['identifier' => $identifier, 'message' => $message];

        $queue = $this->getQueue($queueIdentifier);
        msg_send($queue, 1, $messageContainer);

        if ($awaitsResponse) {

            $retryCount = 0;

            while (true) {
                $responseContainer = $this->receive($identifier, true);

                if ($responseContainer) {
                    return $responseContainer['message'];
                }

                if ($retryCount == $this->responseRetry) {
                    return new Response(Response::STATUS_FAILURE, "Error running command. Please try again.", $identifier);
                }

                $retryCount++;
                usleep($this->sleepTime);
            }
        }

        return null;
    }
}
<?php

namespace phmLabs\ProcessManager\Message;

class Response implements Message
{
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILURE = 'failure';

    private $status;

    /**
     * @var String
     */
    private $message;

    private $identifier;

    public function __construct($status, $message, $identifier)
    {
        $this->status = $status;
        $this->message = $message;
        $this->identifier = $identifier;
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return String
     */
    public function getMessage()
    {
        return $this->message;
    }
}
<?php

namespace phmLabs\ProcessManager\Message\Command;

use phmLabs\ProcessManager\Message\Message;

class CloneCommand implements Message
{
    private $pid;

    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }
}
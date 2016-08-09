<?php

namespace phmLabs\ProcessManager\Message\Command;

use phmLabs\ProcessManager\Message\Message;

class Stop implements Message
{
    private $pid;

    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    public function getPid()
    {
        return $this->pid;
    }
}

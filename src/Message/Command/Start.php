<?php

namespace phmLabs\ProcessManager\Message\Command;

use phmLabs\ProcessManager\Message\Message;

class Start implements Message
{
    private $executable;
    private $name;

    /**
     * Command constructor.
     * @param $executable
     * @param $name
     */
    public function __construct($executable, $name = null)
    {
        $this->executable = $executable;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getExecutable()
    {
        return $this->executable;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
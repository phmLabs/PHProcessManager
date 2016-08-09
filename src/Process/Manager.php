<?php


namespace phmLabs\ProcessManager\Process;

class Manager
{
    private $processes = [];

    public function start($command, $name = null, $restartCount = 0)
    {
        if (array_key_exists($name, $this->processes)) {
            throw new \RuntimeException('Process with name ' . $name . ' already running.');
        }

        $commandLine = 'nohup ' . $command . ' > /dev/null 2>&1 & echo $!';
        exec($commandLine, $output, $return);

        if ($return == 0) {
            $pid = $output[0];
            if (is_null($name)) {
                $name = $pid;
            }
            $this->processes[$name] = ['pid' => $pid, 'command' => $command, 'start' => date('Y-m-d H:m:i'), 'restartCount' => $restartCount];
            return $pid;
        }

        return false;
    }

    public function stop($pid = null, $name = null)
    {
        if (!$name and !$pid) {
            throw new ProcessException("Process pid and name not set. At least one of them must be set.");
        }

        if ($name) {
            if (array_key_exists($name, $this->processes)) {
                $pid = $this->processes[$name];
            } else {
                throw new ProcessException("Process name was not found.");
            }
        }

        if ($pid) {
            foreach ($this->processes as $processName => $process) {
                if ($process['pid'] == $pid) {
                    $name = $processName;
                }
            }
        }

        exec('kill ' . $pid);

        unset($this->processes[$name]);

        return true;
    }


    public function restartDiedProcesses()
    {
        foreach ($this->processes as $name => $process) {
            $pid = $process['pid'];
            if (!posix_getpgid($pid)) {
                unset($this->processes[$name]);
                $this->start($process['command'], $name, $process['restartCount'] + 1);
            }
        }
    }

    public function killProcesses()
    {
        foreach ($this->processes as $name => $process) {
            $this->start(null, $process['pid']);
        }
    }

    public function getProcesses()
    {
        return $this->processes;
    }
}

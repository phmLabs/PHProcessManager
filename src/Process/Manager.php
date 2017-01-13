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
            $this->processes[$name] = [
                'name' => $name,
                'pid' => $pid,
                'command' => $command,
                'start' => date('Y-m-d H:i:s'),
                'restartCount' => $restartCount
            ];

            return $pid;
        }

        return false;
    }

    private function nameExists($name)
    {
        foreach ($this->processes as $process) {
            if ($process['name'] == $name) {
                return true;
            }
        }
        return false;
    }

    public function stop($pid)
    {
        if (array_key_exists($pid, $this->processes)) {
            exec('kill ' . $pid);
            unset($this->processes[$pid]);
            return true;
        } else {
            return false;
        }
    }

    public function cloneProcess($pid)
    {
        if (array_key_exists($pid, $this->processes)) {
            $count = 2;

            $name = preg_replace("^ \([0-9]*\)^", '', $this->processes[$pid]['name']);

            if ($name != "") {
                while ($this->nameExists($name . " (" . $count . ")")) {
                    $count++;
                }
                $newName = $name . " (" . $count . ")";
            } else {
                $newName = "";
            }

            return $this->start(
                $this->processes[$pid]['command'],
                $newName,
                0);
        } else {
            throw new ProcessException("Process ID (pid) not found");
        }
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

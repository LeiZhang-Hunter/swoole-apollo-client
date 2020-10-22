<?php

namespace Framework\Core\Swoole\SwooleProcess;

use  Swoole\Process;
use Swoole\Coroutine;

class SwooleProcessWorker extends SwooleProcess
{

    /**
     * 工作进程
     * @var Process
     */
    private $worker;

    /**
     * 进程id
     * @var int
     */
    private $pid;

    /**
     * 工作进程名字
     * @var
     */
    private $name;

    private $hook;

    public function __construct($hook, $index = 0)
    {
        $this->hook = $hook;
        $this->worker = new Process([$this, "init"]);
        $this->worker->index = $index;
    }

    public function getProcessId()
    {
        return $this->pid;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function output($pid)
    {
        $socket = $this->worker->exportSocket();
        while (SwooleProcessManager::getSyncPrimitive()->get()) {
            $data = $socket->recv();
        }
    }

    public function init($process)
    {
        if ($this->name) {
            swoole_set_process_name($this->name . "-worker");
        }
        call_user_func_array($this->hook, [$process]);
    }

    public function run()
    {
        $this->pid = $this->worker->start();
        //创建一个协程容器
        return $this->pid;
    }
}
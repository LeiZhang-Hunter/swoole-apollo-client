<?php
namespace Framework\Core\Swoole\SwooleProcess;
use Framework\Core\Swoole\SwooleInterface\QueueMonitorInterface;
use  Swoole\Process;
use Swoole\Coroutine;
use Swoole\Timer;
class SwooleProcessMonitor extends SwooleProcess
{
    private $monitor;

    /**
     * 进程id
     * @var int
     */
    private $pid;

    private $interval;

    private $name;

    /**
     * @var QueueMonitorInterface
     */
    public $callback;

    public function __construct($hook)
    {
        $this->callback = $hook;
        $this->monitor = new Process([$this, "init"]);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setInterval(int $interval)
    {
        $this->interval = $interval;
    }

    public function getProcessId()
    {
        return $this->pid;
    }

    public function init($process)
    {
        if ($this->name) {
            swoole_set_process_name($this->name . "-monitor");
        }
        // TODO: Implement init() method.
        $timerId = Timer::tick($this->interval * 1000, $this->callback);
        go(function () use ($timerId){
            while (SwooleProcessManager::getSyncPrimitive()->get()) {
                Coroutine::sleep(0.001);
            }
            Timer::clear($timerId);
        });
    }

    public function run()
    {
        $this->pid = $this->monitor->start();
        //创建一个协程容器
        return $this->pid;
    }
}
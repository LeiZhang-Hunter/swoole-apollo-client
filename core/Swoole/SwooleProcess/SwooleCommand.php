<?php

/**
 * 命令管理
 */
namespace Framework\Core\Swoole\SwooleProcess;

use Framework\Core\Swoole\SwooleInterface\Command;

class SwooleCommand implements Command
{

    private $startCallable;

    private $stopCallable;

    private $reloadCallable;

    /**
     * 解析命令行
     * @return mixed|void
     */
    public function parse()
    {
        global $argv;

        //解析命令行
        if (!isset($argv[2])) {
            $command = "start";
        } else {
            $command = $argv[2];
        }

        if (!$this->startCallable) {
            trigger_error("startCallable must not be null", E_USER_ERROR);
        }

        if (!$this->stopCallable) {
            trigger_error("stopCallable must not be null", E_USER_ERROR);
        }

        if (!$this->reloadCallable) {
            trigger_error("reloadCallable must not be null", E_USER_ERROR);
        }

        switch ($command) {
            case "start":
                 call_user_func($this->startCallable);
                break;

            case "stop":
                call_user_func($this->stopCallable);
                break;

            case "reload":
                call_user_func($this->reloadCallable);
                break;

            default:
                exit("command must be start|stop|reload\n");
                break;
        }

    }

    /**
     * 设置启动的钩子
     * @param $hook
     * @return mixed|void
     */
    public function setStartCallable($hook)
    {
        if (!is_callable($hook))
        {
            trigger_error("start hook must be callable", E_USER_ERROR);
        }
        $this->startCallable = $hook;
    }

    /**
     * 设置停止的钩子
     * @param $hook
     * @return mixed|void
     */
    public function setStopCallable($hook)
    {
        if (!is_callable($hook))
        {
            trigger_error("stop hook must be callable", E_USER_ERROR);
        }
        $this->stopCallable = $hook;
    }

    /**
     * 设置重载的钩子
     * @param $hook
     * @return mixed|void
     */
    public function setReloadCallable($hook)
    {
        if (!is_callable($hook))
        {
            trigger_error("reload hook must be callable", E_USER_ERROR);
        }
        $this->reloadCallable = $hook;
    }
}

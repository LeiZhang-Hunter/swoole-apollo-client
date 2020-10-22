<?php

namespace Framework\Core\Swoole\SwooleInterface;

interface Command
{
    /**
     * 解析
     * @return mixed
     */
    public function parse();

    /**
     * 启动
     * @param $hook
     * @return mixed
     */
    public function setStartCallable($hook);

    /**
     * 停止
     * @param $hook
     * @return mixed
     */
    public function setStopCallable($hook);

    /**
     * 停止
     * @param $hook
     * @return mixed
     */
    public function setReloadCallable($hook);
}
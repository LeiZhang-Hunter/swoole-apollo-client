<?php
namespace Framework\Core\Swoole\SwooleInterface;
interface CoroInterface
{
    /**
     * 初始化
     * @return mixed
     */
    public function init();

    /**
     * 运行
     * @return mixed
     */
    public function run();

    /**
     * 运行结束
     * @return mixed
     */
    public function finish();

    /**
     * 停止运行
     * @return mixed
     */
    public function stop();
}
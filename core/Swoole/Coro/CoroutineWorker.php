<?php

namespace Framework\Core\Swoole\Coro;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use \Swoole\ExitException;

class CoroutineWorker
{

    /**
     * 协程之间通讯的管道
     * @var Channel
     */
    private $channel;

    private $state;

    const RUN = 1;

    const STOP = 0;

    /**
     * @var int
     */
    private $cid;

    /**
     * 协程容器
     * @var CoroutineManager
     */
    private $container;

    /**
     * 参数
     * @var array
     */
    private $params = [];

    /**
     * @var Coro
     */
    private $callable;

    public function __construct($capacity = 50, CoroutineManager $container = null)
    {
        $this->channel = new Channel($capacity);
        $this->container = $container;
    }

    public function getCoroid()
    {
        return $this->cid;
    }

    /**
     * 管道推送
     * @param $data
     * @param int $timeout
     * @return mixed
     */
    public function push($data, $timeout = -1)
    {
        return $this->channel->push($data, $timeout);
    }

    public function pop($timeout = 5)
    {
        return $this->channel->pop($timeout);
    }

    /**
     * 获取通道的状态。
     * @return mixed
     */
    public function getChannelStats()
    {
        return $this->channel->stats();
    }

    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * 仅提供给内部使用外部不要调用,协程销毁时候触发
     */
    public function coroutineDestroy()
    {
        $this->channel->close();
        if ($this->state && $this->container) {
            $coro = new CoroData();
            $coro->handle = $this;
            $coro->callable = $this->callable;
            $coro->params = $this->params;
            return $this->container->push($coro);
        }

        if ($this->container) {
            //减少计数器
            $this->container->getCountDown()->done();
        }
    }

    /**
     * 仅提供给内部使用外部不要调用，协程创建时候触发
     * @param $params
     * @return false
     */
    public function coroutineCreate($params)
    {
        Coroutine::defer([$this, "coroutineDestroy"]);
        //注册协程退出函数
        if (!$this->callable) {
            return false;
        }

        try {
            //初始化
            call_user_func([$this->callable, "init"]);

            while ($this->state) {
                call_user_func_array([$this->callable, "run"], [$params]);
                Coroutine::sleep(1);
            }

            //完成
            call_user_func([$this->callable, "finish"]);
        } catch (ExitException $exception) {
        }
    }

    public function start(Coro $callable, $params)
    {
        $this->state = self::RUN;
        $this->callable = $callable;
        $this->params = $params;
        $this->cid = Coroutine::create([$this, "coroutineCreate"], $params);
        return $this->cid;
    }

    public function stop()
    {
        $this->state = self::STOP;
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->state = self::STOP;
    }
}
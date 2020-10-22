<?php

namespace Framework\Core\Swoole\Coro;

use awheel\Container;
use Framework\Core\Swoole\SwooleProcess\SwooleProcessManager;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;


class CoroutineManager
{
    private $pool = [];

    /**
     * 协程同步原语
     * @var CountDownLatch
     */
    private $countDown;

    private $channel;

    public function __construct($capacity = 20)
    {
        $this->countDown = new CountDownLatch();
        $this->channel = new Channel($capacity);
    }

    /**
     * @return CountDownLatch
     */
    public function getCountDown()
    {
        return $this->countDown;
    }

    /**
     * 注册协程
     * @param $callable
     * @param $params
     * @param $capacity
     * @return bool
     */
    public function reg(Coro $callable, $params, $capacity = 50)
    {
        $coro = new CoroutineWorker($capacity, $this);
        $member = new CoroData();
        $member->handle = $coro;
        $member->callable = $callable;
        $member->params = $params;
        $this->pool[] = $member;
        return true;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function push($data)
    {
        return $this->channel->push($data);
    }

    /**
     * @param int $timeout
     * @return mixed
     */
    public function pop($timeout = 1)
    {
        return $this->channel->pop($timeout);
    }

    /**
     * 启动协程池
     */
    public function monitor()
    {
        Coroutine::create(function () {
            while (SwooleProcessManager::getSyncPrimitive()->get()) {
                $coro = $this->pop();
                /**
                 * @var $coro CoroData
                 */
                if ($coro) {
                    $coro->handle->start($coro->callable, $coro->params);
                }
                Coroutine::sleep(0.001);
            }
            //关闭协程
            $this->channel->close();
            foreach ($this->pool as $worker) {
                /**
                 * @var $worker CoroData
                 */
                $worker->handle->stop();
                $worker->callable->stop();
            }
        });

        Coroutine::create(function () {
            foreach ($this->pool as $worker) {
                /**
                 * @var $worker CoroData
                 */
                $this->getCountDown()->add();
                $worker->handle->start($worker->callable, $worker->params);
            }
            $this->countDown->wait();
        });
    }

    /**
     * 关闭协程池
     */
    public function stop()
    {
        foreach ($this->pool as $worker) {
            /**
             * @var $worker CoroData
             */
            $worker->handle->stop();
        }
    }
}
<?php
namespace Framework\Core\Swoole\Coro;

use \Swoole\Coroutine\WaitGroup;

/**
 * 协程同步工具
 * Class CountDownLatch
 */
class CountDownLatch
{
    private $count;

    public function __construct()
    {
        $this->count = new WaitGroup();
    }

    //添加计数器
    public function add()
    {
        return $this->count->add();
    }

    //减少计数器
    public function done()
    {
        return $this->count->done();
    }

    //等待
    public function wait()
    {
        return $this->count->wait();
    }
}
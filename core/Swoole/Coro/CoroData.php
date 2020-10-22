<?php

//协程池成员
namespace Framework\Core\Swoole\Coro;

class CoroData
{
    /**
     * @var CoroutineWorker
     */
    public $handle;

    /**
     * @var Coro
     */
    public $callable;

    public $params;
}
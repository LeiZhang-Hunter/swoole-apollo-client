<?php

namespace Framework\Core\Swoole\Coro;

use Framework\Core\Swoole\SwooleInterface\CoroInterface;
use Swoole\Coroutine;

/**
 * 协程
 * Class AbstractCoroutine
 * @package Framework\Swoole\Coro
 */
abstract class Coro implements CoroInterface
{
    /**
     * 获取当前的协程id
     * @return mixed
     */
    public static function getCid()
    {
        return Coroutine::getCid();
    }

    /**
     * 获取父协程id
     * @return mixed
     */
    public static function getPcid()
    {
        return Coroutine::getPcid();
    }

    /**
     * 获取指定协程的父协程id
     * @param $cid
     * @return mixed
     */
    public static function getPcidByCid($cid)
    {
        return Coroutine::getPcid($cid);
    }

    public static function exist($cid)
    {
        return Coroutine::exists($cid);
    }

    public static function getContext()
    {
        return Coroutine::getContext();
    }

    public static function getContextByCid($cid)
    {
        return Coroutine::getContext($cid);
    }

    public static function suspend()
    {
        return Coroutine::suspend();
    }

    public static function resume()
    {
        return Coroutine::resume();
    }

    public static function stats()
    {
        return Coroutine::stats();
    }

    public static function getBackTrace($cid = 0, $options = DEBUG_BACKTRACE_PROVIDE_OBJECT, $limit = 0)
    {
        return Coroutine::getBackTrace();
    }

    public static function getElapsed()
    {
        return Coroutine::getElapsed();
    }
}
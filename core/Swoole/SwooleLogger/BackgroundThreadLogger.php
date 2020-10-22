<?php

namespace Framework\Core\Swoole\SwooleLogger;

use Framework\Core\Swoole\SwooleInterface\LoggerAwareInterface;
use Framework\Core\Swoole\SwooleInterface\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

/**
 * 日志背景线程,由于php是一个多进程模型，所以这只是一个异步逻辑，并不是真正的多线程
 * Class BackgroundThreadLogger
 * @package Component\Event\SwooleLogger
 */
class BackgroundThreadLogger implements LoggerAwareInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Swoole\Coroutine\Channel
     */
    private $channel;

    private $run = 1;
    //设置日志
    public function setLogger(LoggerInterface $logger)
    {
        // TODO: Implement setLogger() method.
        $this->logger = $logger;
    }

    /**
     * 启动日志的背景线程
     * @param $wheelTime
     */
    public function start($wheelTime, $size = 30)
    {
        if (Coroutine::getCid() == -1) {
            return false;
        }
        $this->channel = new Channel($size);
        //创建背景日志协程
        Coroutine::create(function () use($wheelTime) {
            while ($this->run) {
                $data = $this->channel->pop($wheelTime);
                try {
                    if ($data) {
                        $this->logger->writeBuffer($data);
                    } else {
                        $this->logger->flush();
                    }
                } catch (\Exception $exception) {
                    echo $exception->getTraceAsString()."\n";
                }
            }
            $this->logger->flush();
        });
        return true;
    }

    /**
     * 通过管道发送数据
     * @param $log
     */
    public function log($log)
    {
        $this->channel->push($log, 5);
    }

    public function stop()
    {
        $this->run = 0;
        //关闭掉管道
        if ($this->channel) {
            $this->channel->close();
        }
    }
}
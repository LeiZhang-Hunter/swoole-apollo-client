<?php

namespace Framework\Core\Swoole\SwooleLogger;


use Framework\Core\Swoole\SwooleInterface\LoggerInterface;

/**
 * Class Logger
 * @package Component\Event\SwooleInterface
 */
class Logger implements LoggerInterface
{
    /**
     * @var $config array
     */
    private $config = [];
    /**
     * 日志缓冲区,由于频繁的file_put_content 效率一定会非常低
     * 众所周知 linux 有大文件预读术，所以说在buffer 合理的情况下 一次性 写入是有优势的
     * 由于php是多进程模型，所以这一切就变得非常简单
     * @var array
     */
    private $buffer = [];

    /**
     * @var string
     */
    public $file;

    /**
     * 文件是否可写
     * @var $writeAble
     */
    public $writeAble;

    /**
     * 是否启动背景线程
     * @var $isStartBackgroundThread bool
     */
    private $isStartBackgroundThread;

    /**
     * @var $backgroundThread BackgroundThreadLogger
     */
    private $backgroundThread;

    /**
     * 缓冲区字段 避免过度频繁的系统调用
     * @var int
     */
    public $pid;

    /**
     * 缓冲区最大长度
     * @var $max_buffer_size
     */
    public $max_buffer_size = 0;


    /**
     * 文件名字
     * @var string
     */
    public $file_name = "";

    /**
     * 背景线程的配置
     * @var array|mixed
     */
    public $startBackgroundThreadConf = [];

    public function __construct($config)
    {
        $this->config = $config;
        $this->file = isset($config["dir"]) ? $config["dir"] : "";
        //启动背景日志，一般在swoole 中使用
        $this->startBackgroundThreadConf = isset($config["background_thread"]) ? $config["background_thread"] : [];
        $this->max_buffer_size = isset($config["max_buffer_size"]) ? $config["max_buffer_size"] : 0;

        if (is_dir($this->file)) {
            $this->writeAble = true;
        }

        $this->file_name = isset($config["file_name"]) ? $config["file_name"] : "default-event";
        if (isset($this->startBackgroundThreadConf["run"]) && $this->startBackgroundThreadConf["run"]) {
            $this->startBackgroundThread($this->startBackgroundThreadConf["interval"]);
        }
    }

    public function emergency($message, array $context = array())
    {
        return $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        return $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        return $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        return $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        return $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        return $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        return $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        return $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * 日志格式
     *
     * 日期 时间 微妙 进程号 级别 正文 源文件和行号
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return bool|void
     */
    public function log($level, $message, array $context = array())
    {
        $date = date("Y-m-d H:i:s");
        $microtime = microtime();
        $microtime = explode(" ", $microtime)[0];
        $trace = debug_backtrace();
        if ($trace) {
            $file = $trace[0]["file"];
            $line = $trace[0]["line"];
        } else {
            $file = "";
            $line = "";
        }

        if (!$this->pid)
            $this->pid = posix_getpid();

        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        $log = "$date $microtime pid:" . $this->pid . " [$level] " . $message . " $file $line\n\n";

        if (!$this->isStartBackgroundThread) {
            write:
            $this->writeBuffer($log);
        } else {
            if (!$this->backgroundThread) {
                goto write;
            }
            $this->backgroundThread->log($log);
        }
        return true;
    }

    /**
     * 写入到缓存buffer中
     * @param $log
     */
    public function writeBuffer($log)
    {
        $this->buffer[] = $log;
        if (sizeof($this->buffer) >= $this->max_buffer_size) {
            $this->flush();
        }
    }

    //合并缓冲区，一次性写入文件后清空缓冲区
    public function flush()
    {
        if ($this->buffer && $this->writeAble) {
            $data = implode("", $this->buffer);
            $date = date("Ymd-H");
            $file = $this->file . "/" . $this->file_name . "-" . $date . ".log";
            file_put_contents($file, $data, FILE_APPEND);
        }
        //清空缓冲区
        $this->buffer = [];
    }

    //启动背景线程,只有在cli下面会运行
    private function startBackgroundThread($wheelTime)
    {
        if (php_sapi_name() == 'cli') {
            $this->isStartBackgroundThread = 1;
            $this->backgroundThread = new BackgroundThreadLogger();
            //写入一个新的logger，防止串读发生
            $logger = clone $this;
            $this->backgroundThread->setLogger($logger);
            if (!$this->backgroundThread->start($wheelTime, $this->max_buffer_size * 2)) {
                $this->isStartBackgroundThread = 0;
            }
        }
    }

    /**
     * 停止背景协程
     */
    public function stopBackgroundThread()
    {
        if (php_sapi_name() == 'cli') {
            if ($this->backgroundThread) {
                $this->backgroundThread->stop();
            }
            //关闭背景协程
            $this->backgroundThread = false;
        }
    }

    public function getBuffer()
    {
        return $this->buffer;
    }

    public function __destruct()
    {
        $this->flush();
        if ($this->backgroundThread) {
            $this->backgroundThread->stop();
        }
    }
}
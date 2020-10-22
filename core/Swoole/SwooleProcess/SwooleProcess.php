<?php
namespace Framework\Core\Swoole\SwooleProcess;

abstract class SwooleProcess
{
    abstract function __construct($hook);
    abstract function init($process);
    abstract function run();
}
<?php
namespace Framework\Core\Swoole\SwooleInterface;

interface SwooleProcessInterface
{
    public function __construct($hook);

    public function run();
}
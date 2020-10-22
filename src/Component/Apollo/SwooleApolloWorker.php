<?php
namespace Component\Apollo;

use Framework\Core\Swoole\Coro\CoroutineManager;
use Framework\Core\Swoole\Coro\CoroutineWorker;
use Swoole\Runtime;
class SwooleApolloWorker
{
    //配置信息
    private $config;

    //app列表
    private $app;

    //集群
    private $cluster;

    //env
    private $env;

    public function __construct($config)
    {
        $this->config = $config;
        $app = $this->config["app"];
        $worker_num = $this->config["worker_num"];
        $size = ceil(sizeof($app) / $worker_num);
        //切割app集群，方便分配给不同的server
        $this->app = array_chunk($app, $size);
    }

    /**
     * 运行worker进程
     * @param $process
     */
    public function run($process)
    {
        Runtime::enableCoroutine(SWOOLE_HOOK_ALL);

        $appList = $this->app[$process->index];
        $coroutineManager = new CoroutineManager();
        foreach ($appList as $appInfo) {

            $apolloConfig = new SwooleApolloConfig();

            if (isset($appInfo["appId"])) {
                $apolloConfig->appId = $appInfo["appId"];
            }

            if (isset($appInfo["localNamespaces"])) {
                $apolloConfig->localNamespaces = $appInfo["localNamespaces"];
            }

            if (isset($appInfo["initNamespaceTime"])) {
                $apolloConfig->initNamespaceTime = $appInfo["initNamespaceTime"];
            }

            if (isset($this->config["env"])) {
                $apolloConfig->env = $this->config["env"];
            }

            if (isset($appInfo["token"])) {
                $apolloConfig->token = $appInfo["token"];
            }

            if (isset($appInfo["cluster"])) {
                $apolloConfig->cluster = $appInfo["cluster"];
            }
            if (isset($this->config["ip"])) {
                $apolloConfig->connectIp = $this->config["ip"];
            }

            if (isset($this->config["port"])) {
                $apolloConfig->port = $this->config["port"];
            }

            if (isset($this->config["server"])) {
                $apolloConfig->server = $this->config["server"];
            }

            if (isset($this->config["watcher"])) {
                $apolloConfig->watcherClass = $this->config["watcher"];
            }

            if (isset($this->config["logger"])) {
                $apolloConfig->logger = $this->config["logger"];
            }

            $apolloConfig->storageRoot = $this->config["save_dir"];
            $apolloClient = new SwooleApolloClient($apolloConfig);

            $coroutineManager->reg($apolloClient, []);
        }

        $coroutineManager->monitor();
    }
}
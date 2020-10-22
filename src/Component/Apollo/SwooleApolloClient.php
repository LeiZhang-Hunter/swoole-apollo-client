<?php
namespace Component\Apollo;

use Framework\Core\Swoole\Coro\Coro;
use Framework\Core\Swoole\SwooleClient\SwooleHttpClient;
use Framework\Core\Swoole\SwooleLogger\Logger;
use Framework\Core\Swoole\SwooleLogger\LogLevel;
use Swoole\Coroutine;

class SwooleApolloClient extends Coro
{
    private $appId;

    private $env = "DEV";

    private $cluster = "cluster";

    private $httpClient;

    private $token;

    /**
     * @var SwooleApolloConfig
     */
    private $config;

    /**
     * @var ApolloApi
     */
    private $api;

    /**
     * 命名空间列表
     * @var
     */
    private $namespaces;

    /**
     * @var ApolloConfigDepositor
     */
    private $storage;

    /**
     * @var array
     */
    private $notifications;

    const RUN = 1;

    const STOP = 0;

    /**
     * 进程运行状态
     * @var int
     */
    private $state = self::RUN;

    private $releaseKey = [];

    /**
     * @var Logger
     */
    private $logger;

    private $initNamespaceTime = 0;

    public function __construct(SwooleApolloConfig $config)
    {
        $this->appId = $config->appId;
        $this->httpClient = new SwooleHttpClient($config->connectIp, $config->port, 60);
        $this->api = new ApolloApi($config);
        $this->config = $config;
        $this->storage = new ApolloConfigDepositor($this->config->storageRoot);
        if ($config->watcherClass) {
            if (class_exists($config->watcherClass)) {
                $watcher = new $config->watcherClass($this->config->storageRoot);
                if ($watcher instanceof ApolloWatcher) {
                    $this->storage = $watcher;
                }
            }
        }
        $this->storage->addRoot($this->appId);
        $this->storage->addRoot($config->cluster);
    }

    /**
     * 初始化获取信息
     * @return false|string
     */
    public function init()
    {
        //没开启日志配置
        if ($this->config->logger && !$this->logger) {
            $this->logger = new Logger($this->config->logger);
        }
        $this->logger->log(LogLevel::INFO, "apollo client ". $this->appId . " init");
    }

    //获取不带缓存的数据
    public function getNoCacheData($namespace)
    {
        $route = sprintf("/configs/%s/%s/%s", $this->appId, $this->cluster, $namespace);

        $params = [];

        if (isset($this->releaseKey[$namespace])) {
            $params["releaseKey"] = $this->releaseKey[$namespace];
        }

        $data = $this->httpClient->request($route, $params);
        if (!$data) {
            return false;
        }

        if (isset($data["releaseKey"])) {
            $this->releaseKey[$namespace] = $data["releaseKey"];
        }

        $configurations = isset($data["configurations"]) ? $data["configurations"] : [];
        if (!$configurations) {
            return false;
        }

        $content = json_encode($configurations);
        $this->storage->save($namespace, $content);

        $this->logger->log(LogLevel::INFO,
            "apollo($this->appId) configuration file ($namespace) has changed; " .
            "The change content is  $content");
    }

    /**
     * 初始化应用下面的命名空间
     * @return false|string
     */
    public function initNamespace()
    {
        //如果说启用本地的namespace
        if ($this->config->localNamespaces) {

            if ($this->notifications) {
                return json_encode($this->notifications);
            } else {
                $this->notifications = [];
                foreach ($this->config->localNamespaces as $namespace) {
                    $unit = [];
                    $unit["namespaceName"] = $namespace;
                    $unit["notificationId"] = -1;
                    $this->notifications[] = $unit;
                }
                return json_encode($this->notifications);
            }

        }

        //如果设置初始化时间是0，那么就会永久有效
        if (!$this->config->initNamespaceTime) {
            if ($this->notifications) {
                return json_encode($this->notifications);
            }
        }

        $nowTime = time();

        //如果说设置了失效时间
        if (($nowTime - $this->initNamespaceTime) < $this->config->initNamespaceTime) {
            if ($this->notifications) {
                return json_encode($this->notifications);
            }
        }

        //获取appId下面的所有namespaces
        $namespaces = $this->api->getNamespaces($this->config->token);
        if (!$namespaces) {
            return false;
        }

        if (isset($namespaces["status"])) {
            $this->logger->log(LogLevel::ERROR, json_encode($namespaces));
            return false;
        }

        $this->initNamespaceTime = time();
        $this->notifications = $namespaces;

        return json_encode($namespaces);
    }

    /**
     * 二维度数字
     * @param $array
     * 字符串
     * @param $field
     */
    private static function setArrayKey($array, $field)
    {
        $data = [];
        foreach ($array as $key=>$value) {
            if (isset($value[$field])) {
                $data[$value[$field]] = $value;
            }
        }
        return $data;
    }

    /**
     * 运行apollo客户端
     * @return false
     */
    public function run()
    {
        $notifications = $this->initNamespace();

        if (!$notifications) {
            return false;
        }

        $notifications = $this->httpClient->request("/notifications/v2", [
            "appId" => $this->appId,
            "cluster" => $this->cluster,
            "notifications" => $notifications
        ]);

        $code = $this->httpClient->getStatusCode();
        //出现断线要重连
        if ($code == -3 || $code == -1) {
            if ($this->state) {
                $this->logger->log(LogLevel::INFO, "apollo client ". $this->appId . " have disconnected");
                $this->httpClient->close();
            }
            return false;
        }

        if ($code != 304 && $code != 200) {
            $this->logger->log(LogLevel::INFO, json_encode($notifications));
            return false;
        }

        if (!$notifications) {
            $this->logger->log(LogLevel::INFO, "apollo client ". $this->appId . " (notifications is empty)");
            return false;
        }

        //将namespace写入到内存中,用作缓存,写入的namespace 可能缺少，这里要做重新的排列组合
        if (!$this->notifications) {
            return false;
        }


        //加入到变量缓存中
        $info = self::setArrayKey($notifications, "namespaceName");
         foreach ($this->notifications as $notificationKey => $notificationInfo) {
             if (isset($info[$notificationInfo["namespaceName"]])) {
                 $this->notifications[$notificationKey] = $info[$notificationInfo["namespaceName"]];
             }
         }

        //拉取不带缓存的接口
        foreach ($notifications as $notification) {
            $this->getNoCacheData($notification["namespaceName"]);
        }

        return true;
    }

    /**
     * 协程退出即将完成的回调
     * @return mixed|void
     */
    public function finish()
    {
        // TODO: Implement finish() method.
        $this->logger->log(LogLevel::INFO, "apollo client ". $this->appId . " finish");
    }

    /**
     * 平滑终止swoole协程
     */
    public function stop()
    {
        $this->logger->log(LogLevel::INFO, "apollo client ". $this->appId . " stop");
        //关闭日志
        if ($this->logger) {
            $this->logger->stopBackgroundThread();
        }
        $this->state = self::STOP;
        $this->httpClient->close();
    }
}
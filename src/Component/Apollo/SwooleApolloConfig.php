<?php
namespace Component\Apollo;

/**
 * Apollo的配置类
 * Class ApolloConfig
 * @package Component\Apollo
 */
class SwooleApolloConfig
{
    //开放平台的url
    public $server;
    //开放平台的token
    public $token;
    //appId
    public $appId;
    //appId的应用环境
    public $env;
    //集群
    public $cluster;
    //连接的ip地址
    public $connectIp;
    //连接的port
    public $port;
    //存储根目录
    public $storageRoot;
    //日志配置
    public $logger;
    //重新请求openApi的时间
    public $initNamespaceTime = 600;
    //本地存储的namespace，如果有值则直接用本地存储的值，不在去请求openApi
    public $localNamespaces;
    //观察者必须是ApolloWatcher的实例
    public $watcherClass;
}
# swoole-apollo-client
 
## 简介

swoole-apollo-client 是一款基于swoole process是实现的携程apollo客户端，继承了swoole协程的高性能特征


## 功能

基本实现java版本的全部功能
- 配置更新实时变更到服务器本地文件
- 自定义钩子监控内容变化

## 运行环境

- [PHP 7.1+](https://github.com/php/php-src/releases)
- [Swoole 4.5+](https://github.com/swoole/swoole-src/releases)
- [Composer](https://getcomposer.org/)

## 运行
composer 安装
```
composer install
```

代码调用
```php
$ip = "192.168.11.45";
$agent = new SwooleApolloManager([
                                     "worker_num" => 1,
                                     "pid_file" => "/data0/run/apollo-agent/run.pid",
                                     "app" => [
                                         [
                                             "appId" => "",
                                             "token" => "",
                                             "cluster" => "",
                                             "localNamespaces" => [],
                                             "initNamespaceTime" => 600,
                                         ]
                                 
                                     ],
                                     "ip" => "$ip",
                                     "port" => 8080,
                                     "server" => "http://$ip:8070",
                                     "env" => "dev",
                                     "save_dir" => "/data/config/",
                                     //日志配置
                                     "logger" => [
                                         "background_thread" => [
                                             "run" => true,
                                             "interval" => 5
                                         ],
                                         "max_buffer_size" => 50,
                                         "dir" => "/data0/log-data/synclog",
                                         "file_name" => "apollo-client"
                                     ],
                                     "service_name" => "php-apollo",
                                     //错误日志位置
                                     "php_error_log" => "/data0/log-data/php_error.log",
                                     //是否开启精灵进程
                                     "daemonize" => true,
                                     "watcher" => \Event\Modules\Apollo\ApolloRedisWatcher::class
                                 ]);
$agent->start();
```

## 启动

```
    php run.php start
```

##停止

```
    php run.php stop
```


## License

php-apollo-client is an open-source software licensed under the MIT

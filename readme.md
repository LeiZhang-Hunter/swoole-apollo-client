# swoole-apollo-client
 
## 简介

swoole-apollo-client 是一款基于swoole process是实现的携程apollo客户端，继承了swoole协程的高性能特征
采用两个进程，一个实时监听，一个定时更新，互不影响，实现配置更新的稳定运行,master分支为php版本，swoole-branch为采用了swoole开发版本，推荐使用
默认采用守护进程的方式，可以结合swoole_server & systemd & supervisor 来实现长期运行，自动重启


## 功能

基本实现java版本的全部功能
- 配置更新实时变更到服务器本地文件
- 自定义钩子监控内容变化

## 运行环境

- [PHP 7.1+](https://github.com/php/php-src/releases)
- [Swoole 4.5+](https://github.com/swoole/swoole-src/releases)
- [Composer](https://getcomposer.org/)
- [apcu](https://github.com/krakjoe/apcu)

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


## License

php-apollo-client is an open-source software licensed under the MIT

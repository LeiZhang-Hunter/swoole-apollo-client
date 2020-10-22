<?php
namespace Framework\Core\Swoole\SwooleClient;

use Swoole\Coroutine\Http\Client;

class SwooleHttpClient
{
    /**
     * @var Swoole\Coroutine\Http\Client
     */
    private $client;

    private $method = "GET";

    private $timeout = 60;

    private $config = [];

    //构造函数
    public function __construct($connectIp, $port, $timeout = 0)
    {
        $this->client = new Client($connectIp, $port);
        if ($timeout) {
            $this->client->set([
                "timeout" => $timeout
            ]);
        }
    }

    /**
     * 设置请求方法
     * @param $method
     */
    public function setMethod($method)
    {
        if ($method) {
            $this->method = $method;
        }
    }

    /**
     * 获取请求方法
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取http请求状态
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->client->statusCode;
    }

    /**
     * 发送get请求
     * @param $url
     * @param array $data
     * @return false|mixed
     */
    public function request($url, $data = [])
    {
        if ($data) {
            $params = http_build_query($data);
            $url .= "?" . $params;
        }
        $result = $this->client->get($url);
        if ($result) {
            return json_decode($this->client->body, 1);
        } else {
            return false;
        }
    }

    /**
     * @param int $timeout
     * @return mixed
     */
    public function recv($timeout = 60)
    {
        $info = $this->client->recv($timeout);
        return $info;
    }

    public function getErrcode()
    {
        return $this->client->errCode;
    }

    public function close()
    {
        $r = $this->client->close();
        return $r;
    }
}
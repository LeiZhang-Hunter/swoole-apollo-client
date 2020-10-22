<?php
namespace Component\Apollo;

use  Swoole\Coroutine;

class ApolloApi
{
    /**
     * 请求接口超时时间
     * @var int
     */
    private $timeOut = 5;

    private $appId;

    /**
     * @var SwooleApolloConfig
     */
    private $config;

    public function __construct(SwooleApolloConfig $config)
    {
        $this->config = $config;
    }

    public function setServer()
    {

    }

    public function setEnv()
    {

    }

    public function setAppId()
    {

    }

    public function setCluster()
    {

    }

    private function curlGet($url)
    {
        $request_number = 0;
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //设置超时时间防止timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization:" . $this->config->token
        ]);

        //获取网页内容
        reconnect:
        $output = curl_exec($ch);
        if ($output === false && $request_number < 4) {
            $request_number++;
            goto reconnect;
        }
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

    public function getNamespaces($token)
    {
        $url = $this->config->server . "/openapi/v1/envs/{$this->config->env}/apps/" .
        $this->config->appId . "/clusters/" .$this->config->cluster. "/namespaces";
        $namespaces = $this->curlGet($url);
        if (!$namespaces) {
            return false;
        }

        $requestResult = json_decode($namespaces, 1);
        if (isset($requestResult["status"])) {
            return $requestResult;
        } else {

            $data = [];

            foreach ($requestResult as $info) {
                $unit = [];
                if ($info["format"] == "properties") {
                    $unit["namespaceName"] = $info["namespaceName"];
                } else {
                    $unit["namespaceName"] = $info["namespaceName"] . "." . $info["format"];
                }
                $unit["notificationId"] = -1;
                $data[] = $unit;
            }

            return ($data);
        }
    }
}
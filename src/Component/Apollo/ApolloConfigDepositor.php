<?php

namespace Component\Apollo;

class ApolloConfigDepositor
{
    private $root;

    /**
     * 初始化根目录
     * ApolloConfigDepositor constructor.
     * @param $root
     */
    public function __construct($root)
    {
        $this->root = $root;
    }

    /**
     * 添加根目录
     * @param $folder
     */
    public function addRoot($folder)
    {
        $this->checkStorage($folder);
        $this->root = realpath($this->root) . "/" . $folder;
    }

    //检查存储路径，存在的话直接返回true，不存在则会先创建
    public function checkStorage($folderNme, $parents = [])
    {
        $parentDir = "";
        if ($parents) {
            $parentDir = implode("/", $parents) . "/";
        }
        $path = realpath($this->root) . "/" . $parentDir . $folderNme;
        if (is_dir($path)) {
            return true;
        }

        $res = mkdir($path);
        if (!$res) {
            throw new \Exception("Failed to create folders " . $path);
        }

        return true;
    }


    public function save($name, $content)
    {
        $path = realpath($this->root) . "/" . $name . ".data";
        return file_put_contents($path, $content, LOCK_EX);
    }
}
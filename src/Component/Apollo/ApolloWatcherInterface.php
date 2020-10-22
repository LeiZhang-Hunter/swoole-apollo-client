<?php
namespace Component\Apollo;

interface ApolloWatcherInterface
{
    public function __construct($root);

    public function addRoot($folder);

    public function save($name, $content);
}
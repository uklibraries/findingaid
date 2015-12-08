<?php
class Controller
{
    protected $params;
    protected $config;

    public function __construct($params = array())
    {
        $this->params = $params;
        $this->config = new Config();
    }
}

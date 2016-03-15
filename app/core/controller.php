<?php
class Controller
{
    protected $params;
    protected $config;

    public function __construct($params = array())
    {
        global $g_config;
        $this->params = $params;
        $this->config = $g_config;
    }
}

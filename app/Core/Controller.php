<?php

namespace App\Core;

class Controller
{
    protected $config;

    public function __construct(protected $params = [])
    {
        global $g_config;
        $this->config = $g_config;
    }
}

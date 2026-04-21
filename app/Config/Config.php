<?php

namespace App\Config;

class Config
{
    private $config = [];
    private $repo = [];
    private $nonuk = null;

    public function __construct()
    {
        $config_file = implode(DIRECTORY_SEPARATOR, [
            APP,
            'Config',
            'config.json',
        ]);
        if (file_exists($config_file)) {
            $this->config = json_decode(file_get_contents($config_file), true);
        }
        $repo_file = implode(DIRECTORY_SEPARATOR, [
            APP,
            'Config',
            'repo.json',
        ]);
        if (file_exists($repo_file)) {
            $this->repo = json_decode(file_get_contents($repo_file), true);
        }
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        } else {
            return null;
        }
    }

    public function getRepo($key)
    {
        if (array_key_exists($key, $this->repo)) {
            return $this->repo[$key];
        } else {
            return $this->repo['default'];
        }
    }

    public function getNonUK($key)
    {
        if (!isset($this->nonuk)) {
            $nonuk_config_file = implode(DIRECTORY_SEPARATOR, [
                APP,
                'Config',
                'nonuk-metadata.json',
            ]);
            if (file_exists($nonuk_config_file)) {
                $this->nonuk = json_decode(file_get_contents($nonuk_config_file), true);
            }
        }
        if (array_key_exists($key, $this->nonuk)) {
            return $this->nonuk[$key];
        } else {
            return false;
        }
    }
}

<?php
class Config
{
    private $config = array();
    private $repo = array();

    public function __construct()
    {
        $config_file = implode(DIRECTORY_SEPARATOR, array(
            APP,
            'config',
            'config.json',
        ));
        if (file_exists($config_file)) {
            $this->config = json_decode(file_get_contents($config_file), true);
        }
        $repo_file = implode(DIRECTORY_SEPARATOR, array(
            APP,
            'config',
            'repo.json',
        ));
        if (file_exists($repo_file)) {
            $this->repo = json_decode(file_get_contents($repo_file), true);
        }
    }

    public function get($key)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }
        else {
            return null;
        }
    }

    public function get_repo($key)
    {
        if (array_key_exists($key, $this->repo)) {
            return $this->repo[$key];
        }
        else {
            return $this->repo['default'];
        }
    }
}

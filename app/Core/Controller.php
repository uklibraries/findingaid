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

    # For links back into ExploreUK, which shares the review prefix.
    public function appPath($path)
    {
        return $this->config->basePath() . '/' . ltrim((string) $path, '/');
    }

    public function assetVersion($path)
    {
        $file_path = implode(DIRECTORY_SEPARATOR, [
            realpath(ROOT),
            'public',
            $path,
        ]);
        $algo = 'sha384';
        $version = $algo . '-' . base64_encode(hash($algo, file_get_contents($file_path), true));
        return $version;
    }
}

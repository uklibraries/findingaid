<?php

namespace App\Core;

# Based on https://github.com/panique/mini
class Application
{
    private $url_params = [];
    private $url_controller = null;
    private $controllers = [
        'home' => \App\Controllers\Home::class,
        'findingaid' => \App\Controllers\Findingaid::class,
        'component' => \App\Controllers\Component::class,
        'overview' => \App\Controllers\Overview::class
    ];

    public function __construct()
    {
        $this->splitUrl();
        $this->checkRedirect();
        $route = $this->url_controller;
        $controllerClass = $this->controllers[$route];
        $controller = new $controllerClass($this->url_params);
        $controller->show();
    }

    private function checkRedirect()
    {
        global $g_config;

        if (php_sapi_name() === 'cli') {
            return;
        }
        if (!isset($this->url_params['id'])) {
            return;
        }
        $target = $g_config->getRedirect($this->url_params['id']);
        if (!$target) {
            return;
        }

        $query = "id=$target";
        if (isset($_GET['overview']) and $_GET['overview'] == 1) {
            $query .= '&overview=1';
        }
        # The app is mounted at / in development and at /fa/findingaid in
        # production, and nginx sets SCRIPT_NAME to match, so derive the base
        # rather than hardcoding it.
        $script_name = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base = rtrim(dirname((string) $script_name), '/');
        header("Location: $base/?$query", true, 301);
        exit;
    }

    private function splitUrl()
    {
        global $argv;
        /* The following block is useful for testing. */
        if (php_sapi_name() === 'cli') {
            if (isset($argv[1]) && preg_match('/^[a-z0-9]+$/', $argv[1])) {
                $_GET['id'] = $argv[1];
                $_GET['cache'] = 1;
            } else {
                sleep(10);
                $_GET['id'] = 'xt73xs5jd22r';
            }
        }
        if (isset($_GET['id'])) {
            $url = trim((string) $_GET['id'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);

            # /:id
            if (count($url) >= 1) {
                $this->url_params = [];
                foreach ($url as $param) {
                    if (strlen($param) > 0) {
                        $this->url_params['id'] = $param;
                        break;
                    }
                }
# temp
                if (isset($_GET['suggest']) and $_GET['suggest'] == 1) {
                    $this->url_params['suggest'] = 1;
                }
                if (isset($_GET['invalidate_cache']) and $_GET['invalidate_cache'] == 1) {
                    $this->url_params['invalidate_cache'] = 1;
                }
                if (preg_match('/^([0-9a-z]+)_([0-9a-z]+)$/', $this->url_params['id'], $matches)) {
                    $this->url_controller = 'component';
                } else {
                    $this->url_controller = 'findingaid';
                }

                if (isset($_GET['overview']) and $_GET['overview'] == 1) {
                    $this->url_controller = 'overview';
                }
            } else {
                $this->url_controller = 'home';
            }
        } else {
            $this->url_controller = 'home';
        }
    }
}

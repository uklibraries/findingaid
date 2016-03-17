<?php
# Based on https://github.com/panique/mini
class Application
{
    private $url_params = array();
    private $url_controller = null;

    public function __construct()
    {
        $this->splitUrl();
        load_path(APP, 'controllers/' . $this->url_controller);
        $this->url_controller = new $this->url_controller($this->url_params);
        $this->url_controller->show();
    }

    private function splitUrl()
    {
        global $argv;
        /* The following block is useful for testing. */
        if (php_sapi_name() === 'cli') {
            if (isset($argv[1]) && preg_match('/^[a-z0-9]+$/', $argv[1])) {
                $_GET['id'] = $argv[1];
                $_GET['cache'] = 1;
            }
            else {
                sleep(10);
                $_GET['id'] = 'xt73xs5jd22r';
            }
        }
        if (isset($_GET['id'])) {
            $url = trim($_GET['id'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);

            # /:id
            if (count($url) >= 1) {
                $this->url_params = array();
                foreach ($url as $param) {
                    if (strlen($param) > 0) {
                        $this->url_params['id'] = $param;
                        break;
                    }
                }
                if (isset($_GET['invalidate_cache']) and $_GET['invalidate_cache'] == 1) {
                    $this->url_params['invalidate_cache'] = 1;
                }
                if (preg_match('/^([0-9a-z]+)_([0-9a-z]+)$/', $this->url_params['id'], $matches)) {
                    $this->url_controller = 'component';
                }
                else {
                    $this->url_controller = 'findingaid';
                }
            }
            else {
                $this->url_controller = 'home';
            }
        }
        else {
            $this->url_controller = 'home';
        }
    }
}

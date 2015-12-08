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

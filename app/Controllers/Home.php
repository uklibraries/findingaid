<?php

namespace App\Controllers;

use App\Core\Controller;
use Mustache_Engine;

class Home extends Controller
{
    public function show()
    {
        $m = new Mustache_Engine();
        echo $m->render(load_template('Home/index'));
    }
}

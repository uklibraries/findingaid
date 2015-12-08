<?php
class Home extends Controller
{
    public function show()
    {
        $m = new Mustache_Engine();
        echo $m->render(load_template('home/index'));
    }
}

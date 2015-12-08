<?php
define('ROOT', dirname(__DIR__));
require_once(implode(DIRECTORY_SEPARATOR, array(ROOT, 'app', 'init.php')));
$app = new Application();

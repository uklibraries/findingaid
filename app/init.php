<?php

require_once __DIR__ . '/init_helpers.php';

use App\Config\Config;
use App\Core\Minter;

load_path(ROOT, 'vendor/autoload');
load_path(APP, 'Core/Brevity');
load_path(APP, 'Core/Render');

$g_config = new Config();
$g_minter = new Minter('fa');

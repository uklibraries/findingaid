<?php
ini_set('memory_limit', '768M');

# Based on https://github.com/panique/mini
# but heavily modified
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

define('APP', implode(DIRECTORY_SEPARATOR, array(
    ROOT,
    'app',
)));

define('CACHE_DIR', implode(DIRECTORY_SEPARATOR, array(
    ROOT,
    'public',
    'cache',
)));

function get_template($path)
{
    $dir = implode(DIRECTORY_SEPARATOR, array(
        APP,
        'views',
    ));
    $pieces = array($dir);
    foreach (explode('/', $path) as $piece) {
        $pieces[] = $piece;
    }
    $file = implode(DIRECTORY_SEPARATOR, $pieces) . '.mustache';
    return $file;
}

function load_template($path)
{
    $file = get_template($path);
    if (file_exists($file)) {
        return file_get_contents($file);
    }
    return null;
}

function get_path($dir, $path)
{
    $pieces = array($dir);
    foreach (explode('/', $path) as $piece) {
        $pieces[] = $piece;
    }
    $file = implode(DIRECTORY_SEPARATOR, $pieces) . '.php';
    return $file;
}

function load_path($dir, $path)
{
    $file = get_path($dir, $path);
    if (file_exists($file)) {
        require_once($file);
    }
}

function get_cache($id)
{
    if (!file_exists(CACHE_DIR)) {
        mkdir(CACHE_DIR);
    }
    $cache_file = implode(DIRECTORY_SEPARATOR, array(
        CACHE_DIR,
        $id,
    ));
    if (file_exists($cache_file)) {
        return $cache_file;
    }
    return false;
}

function get_from_cache($id)
{
    $file = get_cache($id);
    if ($file) {
        return file_get_contents($file);
    }
    else {
        return false;
    }
}

function set_cache($id, $page)
{
    if (!file_exists(CACHE_DIR)) {
        mkdir(CACHE_DIR);
    }
    $cache_file = implode(DIRECTORY_SEPARATOR, array(
        CACHE_DIR,
        $id,
    ));
    file_put_contents($cache_file, $page);
}

load_path(ROOT, 'vendor/autoload');
load_path(APP, 'config/config');
load_path(APP, 'core/brevity');
load_path(APP, 'core/minter');
load_path(APP, 'core/model');
load_path(APP, 'models/component_model');
load_path(APP, 'models/findingaid_model');
load_path(APP, 'core/controller');
load_path(APP, 'core/application');

$g_config = new Config();
$g_minter = new Minter('fa');

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
use Toknot\Boot\Kernel;

/**
 * 
 * @global int $argc
 * @global mix $argv
 * @param string $appdir the APP root dir
 * @return \Toknot\Boot\Kernel|int
 */
function main($appdir = '') {
    global $argc, $argv;
    if (!is_dir($appdir)) {
        echo 'appdir of path is not exist'.PHP_EOL;
        return 1;
    }
    define('APPDIR', realpath($appdir));
    define('TKROOT', __DIR__);
    include __DIR__ . '/Toknot/Boot/Object.php';
    include __DIR__ . "/Toknot/Boot/Kernel.php";
    return Kernel::single($argc, $argv)->run();
}

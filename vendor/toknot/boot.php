<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 * @filesource
 * @package Toknot
 */
use Toknot\Boot\Kernel;

/**
 * main
 * 
 * toknot framework gateway
 * 
 * <code>
 * //example 1
 * main('app_path');
 * 
 * //or  example 2
 * main('app_path','yml');
 * 
 * //or example 3
 * $xmlparse = new YourXMLParse;
 * main('app_path','xml', $xmlparse);
 * </code>
 * 
 * @global int $argc
 * @global mix $argv
 * @param string $appdir        the APP root dir
 * @param boolean $debug        whether enable debug info
 * @param string $confType      config type
 * @param string $parseClass    parse config class,must first include
 * @return Toknot\Boot\Kernel|int
 */
function main($appdir = '', $confType = 'ini', $parseClass = null) {
    global $argc, $argv;
    if (!is_dir($appdir)) {
        echo "$appdir is not exist" . PHP_EOL;
        return 1;
    }
    define('APPDIR', realpath($appdir));
    define('TKROOT', __DIR__);
    include_once __DIR__.'/Toknot/Boot/ObjectAssistant.php';
    include_once __DIR__ . '/Toknot/Boot/ParseConfig.php';
    include __DIR__ . '/Toknot/Boot/Object.php';
    include __DIR__ . "/Toknot/Boot/Kernel.php";
    return Kernel::single($argc, $argv)->run($confType, $parseClass);
}

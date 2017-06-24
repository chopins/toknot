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
use Toknot\Boot\Import;

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
 * @return int
 */
function main($appdir = '', $confType = 'ini', $parseClass = null) {
    global $argc, $argv;
    if (!is_dir($appdir)) {
        echo "$appdir is not exist" . PHP_EOL;
        return 1;
    }
    define('APPDIR', realpath($appdir));
    define('TKROOT', __DIR__);
    include __DIR__ . '/Toknot/Boot/Import.php';

    $import = new Import();
    $import->register();

    $k = Kernel::single($argc, $argv);
    $k->setImport($import);
    return $k->run($confType, $parseClass);
}

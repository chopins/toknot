<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

use Toknot\Boot\Autoloader;
use Toknot\Boot\Kernel;

(function ($argv,$argc) {
    include_once __DIR__ . '/Boot/Autoloader.php';
    $import = new Autoloader(__DIR__);
    $import->register();
    $app = new Kernel($argv, $argc);
    if (PHP_SAPI == 'cli' &&  __FILE__ ==  realpath($argv[0])) {
        $app->bootCLI();
    } else {
        $app->boot();
    }
})($argv,$argc);

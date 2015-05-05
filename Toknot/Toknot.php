<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
use Toknot\Boot\Kernel;

include_once __DIR__ . '/Boot/Kernel.php';

function main() {
    global $argv,$argc;
    $app = new Kernel($argv, $argc);
    if (PHP_SAPI == 'cli' && basename($_SERVER['argv'][0]) == 'Toknot.php') {
        $app->runCLI();
    } else {
        $app->run();
    }
    return $app;
}

$app = main();

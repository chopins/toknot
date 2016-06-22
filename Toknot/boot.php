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
use Toknot\Boot\Log;
use Toknot\Boot\Version;

function main() {
    global $argc,$argv;
    try {
        include_once __DIR__.'/Boot/Version.php';
        Version::checkPHPVersion();
        include_once __DIR__ . '/Boot/Autoloader.php';
        $import = new Autoloader(__DIR__);
        $import->register();
        $app = new Kernel($argv, $argc);
        $app->registerLoadInstance($import);
        if (PHP_SAPI == 'cli' && __DIR__ == dirname(realpath($argv[0]))) {
            $app->bootCLI();
        } else {
            $app->boot();
        }
    } catch (Error $e) {
        Log::colorMessage($e->getMessage(), 'red', true);
        echo $e->getTraceAsString();
    }
}

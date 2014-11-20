<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */
if (CLIEnv()) {
    isset($argv[1]) && CLI($argv[1]);
    echo 'Usage: php Toknot.php command
            command :
                CreateApp           Create a application follow one by one
                GeneratePassword    Use current configure encrypt text
';
    return;
}
include_once __DIR__ . '/Control/Application.php';

function CLIEnv() {
    global $argv;
    return PHP_SAPI == 'cli' && basename($argv[0]) == 'Toknot.php';
}

function CLI($command) {
    global $argv, $argc;
    $filename = __DIR__ . "/Tool/{$command}.php";
    if (file_exists($filename)) {
        include_once $filename;
    } else {
        echo "Undefined $command";
    }
    exit;
}

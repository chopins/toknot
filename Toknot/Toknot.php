<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

use Toknot\Core\Application;

include_once __DIR__ . '/Core/Application.php';

function main() {
    $app = new Application;
    $app->run();
    return $app;
}

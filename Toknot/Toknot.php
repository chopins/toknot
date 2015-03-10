<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

use Toknot\Boot\Kernel;

include_once __DIR__ . '/Boot/Kernel.php';

function main() {
    $app = new Kernel;
    $app->run();
    return $app;
}
$app = main();

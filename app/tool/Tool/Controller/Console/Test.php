<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Share\CommandLine;

/**
 * Test
 *
 */
class Test {

    /**
     * @console test
     */
    public function __construct() {
        foreach ($_SERVER as $k => $v) {
            echo $k . '  |  ' . (filter_has_var(INPUT_SERVER, $k) ? 'yes' : 'no') . PHP_EOL;
        }
    }

}

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
        $cmd = new CommandLine;
        $cmd->autoCompletion(function($re) use($cmd) {
            $cmd->newline();
            echo 'test';
            $cmd->newline();
        });
        $cmd->interactive(function() use($cmd) {
           
        });
        
    }

}

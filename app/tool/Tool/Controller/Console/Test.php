<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

/**
 * Test
 *
 */
class Test {

    public $cmd;

    /**
     * @console test
     */
    public function __construct() {
        $this->cmd = new \Toknot\Share\CommandLine;
        $this->run();
    }

    public function run() {
         $value = '<a onclick="test()">';
        $msg =  \Toknot\Boot\Tookit::filterXSS($value);
        $this->cmd->message($msg);
    }
}

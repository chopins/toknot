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
        $filename = \Toknot\Boot\Kernel::single()->getOption(2);
        $xls = new \Toknot\Share\SimpleXlsx;
        $xls->loadXlsx($filename);
        $xls->readSheet(1, $pos);
        while ($res = $xls->row()) {
            var_dump($res);
        }
                var_dump($pos);

    }

}

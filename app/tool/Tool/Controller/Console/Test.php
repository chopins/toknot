<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Share\SimpleXlsx;

/**
 * Test
 *
 */
class Test {

    /**
     * @console test
     */
    public function __construct() {
        $xlsx = new SimpleXlsx('/home/chopin/Documents/test.xlsx');
        $xlsx->covertAlphabetOrder(26);
        $index = $xlsx->newSheet('test');
        for ($i = 0; $i < 100000; $i++) {
            $row = range(1, 100);
            $xlsx->addRow($row, $index);
        }
        
        $xlsx->save();
    }

}

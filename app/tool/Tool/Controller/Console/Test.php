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
        $index = $xlsx->newSheet();
        for ($i = 0; $i < 10; $i++) {
            $row =['如果设定了的话','查询操作系统主机','默认时区','默认时区','默认时区','默认时区','默认时区'];
            $row = array_merge($row, range(1, 20));
            $xlsx->addRow($row, $index);
        }
        
        $xlsx->save();
    }

}

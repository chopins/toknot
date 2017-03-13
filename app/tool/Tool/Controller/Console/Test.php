<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Share\DB\DBA;

/**
 * Test
 *
 */
class Test {

    /**
     * @console test
     */
    public function __construct() {
        $cnt = 1000;
        $a = [];
        for ($i = 0; $i < $cnt; $i++) {
            $a[] = \Toknot\Boot\Tookit::numberId(14);
            usleep(100);
        }
        $sum = 0;
        foreach(array_count_values($a) as $n) {
            if($n > 1) {
                $sum++;
            }
        }
        var_dump($sum/$cnt);
    }

}

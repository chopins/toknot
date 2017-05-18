<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller\Console;

/**
 * Test
 *
 */
class Test {

    public function __construct() {
        $p = new \Toknot\Share\Process\Process();
        $db = \Toknot\Share\DB\DBA::table('user');
   
        $status = $p->processPool(10);
        if ($status) {
            exit;
        }

        \Toknot\Boot\Tookit::iter(2, function($i) use($db, $p) {
            $db->findKeyRow(1);
            sleep(1);
        });

    }

}

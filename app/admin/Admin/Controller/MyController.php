<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller;

class MyController {

    public function test() {
        $t = \Toknot\Share\DB\DBA::table('user');
        //$res = $t->getList(false, 10);
        var_dump($t->primaryKey());
        var_dump($t->isCompoundKey());
    }

}

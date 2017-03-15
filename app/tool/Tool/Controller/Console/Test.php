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

    /**
     * @console test
     */
    public function __construct() {
        $a = null;

        $f = $this->check();
        
        var_dump($f('a'));
    }

    public function check() {
        ;
        eval('$v = get_defined_vars();$f = function($k) use($v) { return array_key_exists($k,$v);};');
        return $f;
    }
}

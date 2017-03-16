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
        $c = \Toknot\Boot\Kernel::single()->call['controller'];
        $route = \Toknot\Boot\Kernel::single()->routerIns()->findRouteByController($c);
        var_dump($route);
    }

   
}

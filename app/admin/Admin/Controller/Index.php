<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller;

use Admin\Controller\Lib\Common;

/**
 * Description of Index
 *
 * @author chopin
 */
class Index extends Common {

    /**
     * @route
     */
    public function index() {
        $u = $this->model('user');
        $this->v->pageNav = '个人';

        $this->setTitle('ProcessHub');
        $u->getKeyValue(1);
        $this->view('Index');
    }

}

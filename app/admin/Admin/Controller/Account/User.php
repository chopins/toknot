<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller\Account;

use Admin\Controller\Lib\Common;

class User extends Common {

    /**
     * @route
     */
    public function index() {
        $this->setTitle('个人信息');
        $this->v()->pageNav = '个人信息';
        $this->v()->leftMenuSelected = 'account';
        $this->view('account.index');
    }

}

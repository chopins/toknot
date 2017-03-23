<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Middleware;

use Toknot\Share\Controller;

class UserAuth extends Controller {

    public function __construct() {
        //$this->startSession();
    }
    
    public function checkLogin() {
        //if(empty($_SESSION['uid'])) {
//           $this->redirect('login-view'); 
        //}

    }

}

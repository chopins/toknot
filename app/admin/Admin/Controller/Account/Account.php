<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\Controller\Account;

use Toknot\Share\Controller;

class Account extends Controller {

    public function __construct() {
        $this->setLayout($this->config('app', 'default_layout'));
        $this->enableCsrf();

        $this->startSession();
    }

    /**
     * @route
     */
    public function login() {
        $this->setTitle('Login');
        $this->v('pageNav', '登录');
        $this->v('signup', $this->url('signup-view'));
        $this->v('login', $this->url('login-submit'));

        $this->view('account.login');
    }

    /**
     * @route
     */
    public function postLogin() {
        if ($this->checkCsrf()) {
            echo 'pass';
        } else {
            echo 'reject';
        }
    }

    /**
     * @route
     */
    public function signup() {
        $this->setTitle('Signup');
        $this->v('login', $this->url('login-view'));
        $this->v('signup', $this->url('signup-submit'));

        $this->view('account.signup');
    }

    /**
     * @route
     */
    public function postSignup() {
        
    }
    
    /**
     * @route
     */
    public function logout() {
        
    }


}
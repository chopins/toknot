<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Admin\AdminBase;
use Toknot\Lib\User\UserClass;

class Login extends AdminBase{

    protected $permissions = 0777;

    public function GET() {
        
        $this->D->act = 'login';
        $this->D->message = '';
        $this->display('login');
    }

    public function POST() {
        $userName = $this->getPOST('username');
        $password = $this->getPOST('password');
        $user = UserClass::login($userName, $password);
        if ($user) {
            if (null!== $this->getPOST('week')) {
                $user->setLoginExpire('1w');
            }
            $this->setAdminLogin($user);
            $this->redirectController('\Index');
        } else {
            $this->D->act = 'login';
            $this->D->message = 'Username or password invaild';
            $this->display('login');
        }
    }
    public function logout() {
        $this->currentUser->logout();
        $this->SESSION->unsetSession();
    }

}


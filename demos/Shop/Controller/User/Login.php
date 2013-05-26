<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Shop\Controller\User;
use Shop\ShopBase;

class Login extends ShopBase {
    public $perms = 0777;
    public function GET() {
        $currentUser = $this->FMAI ->getCurrentUser('username');
        $currentUser->info();
    }
    public function POST() {
        ;
    }
}
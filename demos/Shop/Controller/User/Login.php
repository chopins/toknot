<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Shop\Controller\User;
use Shop\ShopContext;
class Login extends ShopContext {
    public function GET() {
        $this->view->newPage('index');
        $meta = $this->view->newMeta('http-equiv="content-type" content="text/html; charset=UTF-8"');
        $title = $this->view->title('test');
        
        $this->view->display();
    }
    public function POST() {
        ;
    }
}
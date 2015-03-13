<?php

namespace Shop\Controller;

use Shop\Header;

class Index extends Header {

    protected $permissions = 0770;
    protected $gid = 0;
    protected $uid = 0;
    protected $operateType = 'r';

    public function GET() {
        //$database = $this->AR->connect();
        $router = \Toknot\Boot\Router::getClassInstance();
        print $router->getResourceType();

        print "hello world";
    }

}

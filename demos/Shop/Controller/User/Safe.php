<?php

namespace Shop\Controller\User;
use Toknot\Boot\Router;
/**
 * Description of Safe
 *
 * @author chopin
 */
class Safe {
    public function GET() {
        //$database = $this->AR->connect();
        $router = Router::getClassInstance();
        var_dump($router->getParams(0));
        print "hello world safe";    
        
    }
}

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace ToknotUnit;

class RouterTest extends TestCase {

    /**
     * @expectedException Toknot\Boot\Exception\NotFoundException
     * @expectedExceptionMessage Controller \Controller\TestController\SubController Not Found
     */
    public function testRouterPath() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/testController/SubController/12/test';
        $router = new \Toknot\Boot\Router();
        $CFG = \Toknot\Config\ConfigLoader::CFG();

        $CFG->App = new \Toknot\Boot\ArrayObject(array('routerMode' => 'ROUTER_PATH',
            'rootNamespace' => '\TestController',
            'routerDepth' => 2));

        $router->loadConfigure();
        $router->routerRule();
        $this->assertEquals($router->getParams(0), 12);
        $this->assertEquals($router->getParams(1), 'test');
        $router->invoke();
    }

    /**
     * @expectedException Toknot\Boot\Exception\NotFoundException
     * @expectedExceptionMessage Controller \Controller\TestController\SubController Not Found
     */
    public function testRouterGetQuery() {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = array();
        $_GET['/TestController/SubController'] = '';
        $router = new \Toknot\Boot\Router();
        $CFG = \Toknot\Config\ConfigLoader::CFG();

        $CFG->App = new \Toknot\Boot\ArrayObject(array('routerMode' => 'ROUTER_GET_QUERY'));
        $router->loadConfigure();
        $router->routerRule();
        $router->invoke();
    }

}

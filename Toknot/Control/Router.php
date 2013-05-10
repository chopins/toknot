<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

use Toknot\Di\Object;
use Toknot\Control\RouterInterface;
use Toknot\Control\AppContext;

final class Router extends Object implements RouterInterface {

    private $routerMode = 0;
    private $routerNameSpace = '';
    private $spacePath = '\\';

    /**
     * singleton 
     * 
     * @static
     * @access public
     * @return void
     */
    public static function singleton() {
        return parent::__singleton();
    }

    public function routerRule() {
        if ($this->routerMode == 1) {
            $this->spacePath = '\\' . str_replace('.', '\\', $_GET['r']);
        } else {
            $this->spacePath = '\\' . str_replace('/', '\\', $_SERVER['REQUEST_URI']);
        }
    }

    public function invoke() {
        $method = $this->getRequestMethod();
        $invokeClass = "{$this->routerNameSpace}{$this->spacePath}";
        $invokeClassReflection = new \ReflectionClass($invokeClass);
        if ($invokeClassReflection->hasMethod($method)) {
            $invokeObject = $invokeClassReflection->newInstance();
            $context = AppContext::singleton();
            $invokeObject->$method($context);
        } else {
            throw new \Toknot\Exception\StandardException('Not Support Method');
        }
    }

    public function runtimeArgs($mode = 0) {
        $this->routerMode = $mode;
    }

    public function routerSpace($appspace) {
        $this->routerNameSpace = $appspace;
    }

    private function getRequestMethod() {
        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = getenv('REQUEST_METHOD');
        }
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * __construct 
     * 
     * @access protected
     * @return void
     */
    public function __construct() {
        
    }

    /**
     * set router mode and current only support GET query and PATH mode
     * 
     * @param integer $mode  router mode ,set 1 use GET query mode, default is 0 that PATH mode
     */
    public function setRouterMode($mode = 0) {
        if ($mode == 1) {
            $this->routerMode = 1;
        }
    }

}

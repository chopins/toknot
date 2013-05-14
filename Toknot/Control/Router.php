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
use Toknot\Exception\StandardException;
use Toknot\Exception\BadClassCallException;
use Toknot\Control\AppContext;
use \ReflectionClass;

class Router extends Object implements RouterInterface {

    /**
     * router mode, default 0 is PATH mode, 1 is GET query mode and use $_GET['r']
     * to is invoke class, the property set by {@see Application::run} 
     * be invoke with passed of 4th parameter, Toknot default router of runtimeArgs method
     * will set be passed of first parameter
     * 
     * @var integer 
     * @access private
     */
    private $routerMode = 0;

    /**
     * router class of root namespace , usual it is application root namespace
     *
     * @var string 
     * @access private
     */
    private $routerNameSpace = '';

    /**
     * visit URI to application namespace route
     *
     * @var string
     * @access private 
     */
    private $spacePath = '\\';
    private $routerPath = '';
    private $defaultClass = '\Index';
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
    
    /**
     * Set Controler info that under application, if CLI mode, will set request method is CLI
     * 
     */
    public function routerRule() {
        if ($this->routerMode == 1) {
            if(empty($_GET['r'])) {
                $this->spacePath = $this->defaultClass;
            } else {
                $this->spacePath = '\\' . str_replace('.', '\\', $_GET['r']);
            }
        } else {
            if(PHP_SAPI == 'cli') {
                $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
            }
            $this->spacePath = str_replace('/', '\\', $_SERVER['REQUEST_URI']);
            $this->spacePath = $this->spacePath == '\\' ? $this->defaultClass : $this->spacePath;
        }
    }

    public function routerPath($path) {
        $this->routerPath = $path;
    }
    public function defaultInvoke($defaultClass) {
        $this->defaultClass = $defaultClass;
    }
    /**
     * Invoke Application Controller, the method will call application of Controller what is
     * $this->routerNameSpace\Controller{$this->spacePath}, and router action by request method
     * 
     * @param \Toknot\Control\AppContext $appContext
     * @throws BadClassCallException
     * @throws StandardException
     */
    public function invoke(AppContext $appContext) {
        $method = $this->getRequestMethod();
        $invokeClass = "{$this->routerNameSpace}\Controller{$this->spacePath}";
        if (!class_exists($invokeClass, true)) {
            throw new BadClassCallException($invokeClass);
        }
        $invokeClassReflection = new ReflectionClass($invokeClass);
        if ($invokeClassReflection->hasMethod($method)) {
            $invokeObject = $invokeClassReflection->newInstance($appContext);
            $invokeObject->$method();
        } else {
            throw new StandardException("Not Support Request Method ($method)");
        }
    }
    
    /**
     * implements {@see Toknot\Control\RouterInterface} of method , the method only 
     * set toknot defualt router of run mode {@see Toknot\Control\Router::$routerMode}
     * 
     * @param type $mode    router of run mode be passed by Application::run of 4th parameter
     */
    public function runtimeArgs($mode = 0) {
        $this->routerMode = $mode;
    }
    
    /**
     * implements {@see Toknot\Control\RouterInterface} of method, the method set Application
     * of top namespace 
     * 
     * @param string $appspace
     */
    public function routerSpace($appspace) {
        $this->routerNameSpace = $appspace;
    }
    
    /**
     * get HTTP request use method
     * 
     * @return string
     */
    private function getRequestMethod() {
        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = getenv('REQUEST_METHOD');
        }
        if(empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'CLI';
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

}

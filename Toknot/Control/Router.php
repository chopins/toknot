<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

use Toknot\Control\RouterInterface;
use Toknot\Exception\StandardException;
use Toknot\Exception\BadClassCallException;
use Toknot\Control\FMAI;
use \ReflectionClass;
use Toknot\Control\StandardAutoloader;
use Toknot\Di\FileObject;

class Router implements RouterInterface {

    /**
     * router mode, default 0 is PATH mode, 1 is GET query mode and use $_GET['r']
     * to is invoke class, the property set by {@see Toknot\Control\Application::run} 
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

    /**
     * the application path
     *
     * @var string
     * @access private
     */
    private $routerPath = '';

    /**
     * like webserver set index.html, if set null will return 404 when no debug and develop will throw 
     * {@see BadClassCallException} exception
     *
     * @var string
     * @access private
     */
    private $defaultClass = '\Index';

    /**
     * Alow namespace max level under Controller, only on Router::ROUTER_PATH in effect
     * if set 0 will no limit
     *
     * @var int 
     * @access private
     */
    private $routerDepth = 1;

    /**
     * Array of url path query without Controller path
     *
     * @var array 
     */
    private $suffixPart = array();

    /**
     * the class be invoked when the request controller not found, the class
     * namspace under Application root namespace
     *
     * @var string
     */
    private $notFuondController = null;

    /**
     * the class be invoked when the request controller not has support the method of the http request, 
     * the class namspace under Application root namespace
     *
     * @var string
     */
    private $methodNotAllowedController = null;

    /**
     * use URI of path controller invoke application controller of class
     */

    const ROUTER_PATH = 1;

    /**
     * use requset query of $_GET['c'] parameter controller invoke application controller of class
     */
    const ROUTER_GET_QUERY = 2;

    /**
     * Set Controler info that under application, if CLI mode, will set request method is CLI
     * 
     */
    public function routerRule() {
        if ($this->routerMode == self::ROUTER_GET_QUERY) {
            if (empty($_GET['c'])) {
                $this->spacePath = $this->defaultClass;
            } else {
                $this->spacePath = '\\' . strtr($_GET['c'], '.', '\\');
            }
        } else {
            if (PHP_SAPI == 'cli') {
                if (isset($_SERVER['argv'][1])) {
                    $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
                } else {
                    $_SERVER['REQUEST_URI'] = '/';
                }
            }
            if (($pos = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
                $urlPath = substr($_SERVER['REQUEST_URI'], 0, $pos);
            } else {
                $urlPath = $_SERVER['REQUEST_URI'];
            }
            $spacePath = strtr($urlPath, '/', StandardAutoloader::NS_SEPARATOR);
            $spacePath = $spacePath == StandardAutoloader::NS_SEPARATOR ? $this->defaultClass : $spacePath;
            if ($this->routerDepth > 0) {
                $name = strtok($spacePath, StandardAutoloader::NS_SEPARATOR);
                $this->spacePath = '';
                $depth = 0;
                while ($name) {
                    if ($depth <= $this->routerDepth) {
                        $this->spacePath .= StandardAutoloader::NS_SEPARATOR . ucfirst($name);
                    } else {
                        $this->suffixPart[] = $name;
                    }
                    $name = strtok(StandardAutoloader::NS_SEPARATOR);
                    $depth++;
                }
            } else {
                $this->spacePath = $spacePath;
            }
        }
    }

    /**
     * set application path, implements RouterInterface
     * 
     * @param string $path
     */
    public function routerPath($path) {
        $this->routerPath = $path;
    }

    public function defaultInvoke($defaultClass) {
        $this->defaultClass = $defaultClass;
    }

    private function invokeNotFoundController(&$invokeClass) {
        if (DEVELOPMENT) {
            throw new BadClassCallException($invokeClass);
        }
        header('404 Not Found');
        if ($this->notFuondController === null) {
            $invokeClass = "{$this->routerNameSpace}\{$this->notFuondController}";
            if (!class_exists($invokeClass, true)) {
                die('404 Not Found');
            }
        } else {
            die('404 Not Found');
        }
    }

    /**
     * Invoke Application Controller, the method will call application of Controller what is
     * $this->routerNameSpace\Controller{$this->spacePath}, and router action by request method
     * 
     * @param \Toknot\Control\FMAI $appContext
     * @throws BadClassCallException
     * @throws StandardException
     */
    public function invoke(FMAI $FMAI) {
        $method = $this->getRequestMethod();
        $invokeClass = "{$this->routerNameSpace}\Controller{$this->spacePath}";
        $classFile = StandardAutoloader::transformClassNameToFilename($invokeClass, $this->routerPath);
        $classExist = false;
		$caseClassFile =FileObject::fileExistCase($classFile); 
        if ($caseClassFile) {
            include_once $caseClassFile;
			$this->spacePath = strtok(basename($caseClassFile),'.');
        	$invokeClass = "{$this->routerNameSpace}\Controller\\{$this->spacePath}";
            $classExist = class_exists($invokeClass, false);
        }
        if (!$classExist) {
            $dir = StandardAutoloader::transformClassNameToFilename($invokeClass, $this->routerPath);
            if (is_dir($dir) && $this->defaultClass != null) {
                $invokeClass = "{$this->routerNameSpace}\Controller{$this->spacePath}\{$this->defaultClass}";
                $classFile = StandardAutoloader::transformClassNameToFilename($invokeClass, $this->routerPath);
                if (is_file($classFile)) {
                    include $classFile;
                }
                if (!class_exists($invokeClass, false)) {
                    $invokeClass = $this->invokeNotFoundController();
                }
            } else {
                $this->invokeNotFoundController($invokeClass);
            }
        }
        $invokeClassReflection = new ReflectionClass($invokeClass);

        $FMAI->setURIOutRouterPath($this->suffixPart);
        $FMAI->requestMethod = $method;

        $invokeObject = $invokeClassReflection->newInstance($FMAI);
        $stat = $FMAI->invokeBefore($invokeClassReflection);
        if ($stat === true) {
            if (!$invokeClassReflection->hasMethod($method)) {
                if (DEVELOPMENT) {
                    throw new StandardException("Not Support Request Method ($method)");
                } else {
                    header('405 Method Not Allowed');
                    if ($this->methodNotAllowedController === null) {
                        $invokeClass = "{$this->routerNameSpace}\{$this->methodNotAllowedController}";
                        $classFile = StandardAutoloader::transformClassNameToFilename($invokeClass, $this->routerPath);
                        if (is_file($classFile)) {
                            include $classFile;
                        }
                        if (!class_exists($invokeClass, false)) {
                            die('405 Method Not Allowed');
                        }
                    } else {
                        die('405 Method Not Allowed');
                    }
                }
            }
            $invokeObject->$method();
        }
        $FMAI->invokeAfter($invokeClassReflection);
    }

    /**
     * implements {@see Toknot\Control\RouterInterface} of method , the method
     * set toknot defualt router of run mode {@see Toknot\Control\Router::$routerMode} and
     * set the controller max level namespace which on PATH mode in effect
     * 
     * @param int $mode  Router of run mode Use set {@see Toknot\Control\Router::ROUTER_PATH} is default or 
     *                    {@see Toknot\Control\Router::ROUTER_GET_QUERY}, only use framework router
     *                    the parameter is set router mode
     * @param int $routerDepth  The under controller of namespace max level, if set 0 will not limit
     * @param string $notFound  When controller not found be invoked simailar web 404 page set, The class
     *                           namespace under Application root, default is null
     * @param string $methodNotAllowed  When controller not has method be invoked simailar web 405 page set
     *                                  The class namespace under Application root ,default is null
     */
    public function runtimeArgs($mode = self::ROUTER_PATH, $routeDepth = 1, $notFound = null, $methodNotAllowed = null) {
        $this->routerMode = $mode;
        $this->routerDepth = $routeDepth;
        $this->notFuondController = $notFound;
        $this->methodNotAllowedController = $methodNotAllowed;
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
        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'CLI';
        }
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * __construct reject new of outside
     * 
     * @access public
     * @return void
     */
    public function __construct() {
        
    }

}

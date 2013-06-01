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
use Toknot\Control\FMAI;
use \ReflectionClass;
use Toknot\Control\StandardAutoloader;
use Toknot\View\ViewCache;

class Router extends Object implements RouterInterface {

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
    private $suffixPart = array();

    /**
     * use URI of path controller invoke application controller of class
     */

    const ROUTER_PATH = 1;

    /**
     * use requset query of $_GET['c'] parameter controller invoke application controller of class
     */
    const ROUTER_GET_QUERY = 2;

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
        if ($this->routerMode == self::ROUTER_GET_QUERY) {
            if (empty($_GET['c'])) {
                $this->spacePath = $this->defaultClass;
            } else {
                $this->spacePath = '\\' . str_replace('.', '\\', $_GET['c']);
            }
        } else {
            if (PHP_SAPI == 'cli') {
                $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
            }
            if (($pos = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
                $urlPath = substr($_SERVER['REQUEST_URI'], 0, $pos);
            } else {
                $urlPath = $_SERVER['REQUEST_URI'];
            }
            $spacePath = str_replace('/', StandardAutoloader::NS_SEPARATOR, $urlPath);
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
        if (!class_exists($invokeClass, true)) {
            $dir = StandardAutoloader::transformClassNameToFilename($invokeClass, $this->routerPath);
            if (is_dir($dir) && $this->defaultClass != null) {
                $invokeClass = "{$this->routerNameSpace}\Controller{$this->spacePath}\{$this->defaultClass}";
                if (!class_exists($invokeClass, true)) {
                    if (DEVELOPMENT) {
                        throw new BadClassCallException($invokeClass);
                    } else {
                        header('404 Not Found');
                        die('404 Not Found');
                    }
                }
            } else {
                if (DEVELOPMENT) {
                    throw new BadClassCallException($invokeClass);
                } else {
                    header('404 Not Found');
                    die('404 Not Found');
                }
            }
        }
        $invokeClassReflection = new ReflectionClass($invokeClass);
        if ($invokeClassReflection->hasMethod($method)) {
            $FMAI->setURIOutRouterPath($this->suffixPart);
            $FMAI->requestMethod = $method;
            $invokeObject = $invokeClassReflection->newInstance($FMAI);
            if($invokeClassReflection->isSubclassOf('\Toknot\User\ClassUserControl')
                    && $FMAI->getAccessStatus() === false) {
                $accessDeniedController = $FMAI->getAccessDeniedController();
                $invokeObject = new $accessDeniedController($FMAI);
                $invokeObject->GET();
                return;
            }
            if ($method == 'GET' && ViewCache::$enableCache) {
                ViewCache::outPutCache();
            }
            
            if (ViewCache::$cacheEffective == false) {
                $invokeObject->$method();
            }
        } else {
            if (DEVELOPMENT) {
                throw new StandardException("Not Support Request Method ($method)");
            } else {
                header('405 Method Not Allowed');
                die('405 Method Not Allowed');
            }
        }
    }

    /**
     * implements {@see Toknot\Control\RouterInterface} of method , the method
     * set toknot defualt router of run mode {@see Toknot\Control\Router::$routerMode} and
     * set the controller max level namespace which on PATH mode in effect
     * 
     * @param int $mode  router of run mode be passed by {@see Toknot\Control\Application::run()} of 4th parameter
     * @param int $name  the under controller of namespace max level, if set 0 will not limit
     */
    public function runtimeArgs($mode = self::ROUTER_PATH, $routeDepth = 1) {
        $this->routerMode = $mode;
        $this->routerDepth = $routeDepth;
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
     * __construct 
     * 
     * @access protected
     * @return void
     */
    public function __construct() {
        
    }

}

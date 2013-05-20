<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

include_once dirname(__FILE__) . '/StandardAutoloader.php';

use Toknot\Control\StandardAutoloader;
use Toknot\Exception\StandardException;
use Toknot\Contorl\Exception\PHPVersionException;
use Toknot\Exception\BadNamespaceException;
use Toknot\Exception\BadClassCallException;
use Toknot\Control\FMAI;
use \ReflectionClass;

/**
 * Toknot main class and run framework
 * 
 * <code>
 * use Toknot\Control\Application;
 * require_once '/path/Toknot/Control/Application.php';
 * $app = new Application;
 * $app->run('\AppTopNamespace', '/path/AppPath');
 * </code>
 * 
 */
class Application {
    
    /**
     * This save toknot of standard autoloader ({@see Toknot\Control\StandardAutoloader}) instance
     * current do not use user's autoloader class, so will call toknot standard
     * autoloader class when application instantiate 
     *
     * @var mixed
     * @access private 
     */
    private $standardAutoLoader = null;
    
    /**
     * This is router class name, if do not use setUserRouter method set a router 
     * after new Application and before invoke run method, and will use toknot default router
     * router class name must use full namespace rather than short name
     *
     * @var string 
     * @access private
     */
    private $routerName = '\Toknot\Control\Router';

    /**
     * The construct parameters only receive PHP in CLI mode passed  argv and argc 
     * parameters and save there to $_SERVER via iniEnv method
     * 
     * <code>
     * use Toknot\Control\Application;
     * 
     * require_once '/path/Toknot/Control/Application.php';
     * 
     * $app = new Application;
     * </code>
     * 
     * if PHP on CLI mode, and use below  code on command line:
     * <code>
     * php App.php option1 option2
     * </code>
     * in php script like below:
     * <code>
     * $app = new Application($argv, $argc);
     * 
     * function printArg() {
     *      var_dump($_SERVER['argc'][0]); // print option1
     *      var_dump($_SERVER['argc'][1]); // print option2
     *      var_dump($_SERVER['argc']); //print number of args
     * }
     * </code>
     * 
     * @param array $argv Array of arguments passed to script
     * @param integer $argc The number of  passed to script
     */
    public function __construct($argv = array(), $argc = 0) {
        $this->iniEnv($argv, $argc);
        $this->registerAutoLoader();
    }
    
    /**
     * set CLI mode passed arguments and set Error Handler
     * 
     * @param type $argv    Array of arguments passed to script
     * @param type $argc    The number of  passed to script
     * @throws PHPVersionException  Toknot current support php of version on 5.3
     *                               or higher, otherwise throw the Exception
     * @return void
     */
    private function iniEnv($argv, $argc) {
        $this->checkSuperglobals();
        if (PHP_SAPI == 'cli' && !isset($_SERVER['argv'])) {
            $_SERVER['argc'] = $argc;
            $_SERVER['argv'] = $argv;
        }

        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            throw new PHPVersionException();
        }

        set_error_handler(array($this, 'errorReportHandler'));
        clearstatcache();
    }

    /**
     * check php superglobals whether be set or 
     * not will set $_SERVER,$_GET, and if php is less 5.4, will set 
     * $_SERVER['REQUEST_TIME_FLOAT'] that is http request time with microsecond
     * 
     * @access private
     * @return void
     */
    private function checkSuperglobals() {
        $variables_order = strtoupper(ini_get('variables_order'));

        if (strpos($variables_order, 'S') === false) {
            $_SERVER['_'] = getenv('_');
            $_SERVER['REQUEST_URI'] = getenv('REQUEST_URI');
            $_SERVER['SCRIPT_FILENAME'] = getenv('SCRIPT_FILENAME');
            $_SERVER['DOCUMENT_URI'] = getenv('DOCUMENT_URI');
            $_SERVER['REQUEST_METHOD'] = getenv('REQUEST_METHOD');
            $_SERVER['PATH_INFO'] = getenv('PATH_INFO');
            $_SERVER['SERVER_ADDR'] = getenv('SERVER_ADDR');
            $_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
            $_SERVER['SERVER_NAME'] = getenv('SERVER_NAME');
            $_SERVER['QUERY_STRING'] = getenv('QUERY_STRING');
        }

        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
        }

        if (strpos($variables_order, 'G') === false) {
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }
    }
   

    /**
     * Run application, the method will invoke router with implements interface of 
     * {@link Toknot\Control\RouterInterface} of all method, Toknot Freamework default
     * invoke class under application of Controller Dicetory, scan file path is under $appPath 
     * parameter set path(like: /path/appPath/Controller). The class be invoke by toknot of router 
     * invoke method with passed instance of Toknot 
     * {@see \Toknot\Control\AppContext}, you can receive the object of instance when class construct
     * 
     * Usual use toknot of router, run framework like below:
     * <code>
     * use Toknot\Control\Application;
     *
     * require_once './Toknot/Control/Application.php';
     *
     * $app = new Application;
     * $app->run('\AppTopNamespace', '/path/AppPath');
     * </code>
     * 
     * if use application of router ,use {@see \Toknot\Control\Application::setUserRouter} define,
     * run framework like below:
     * <code>
     * use Toknot\Control\Application;
     *
     * require_once './Toknot/Control/Application.php';
     *
     * $app = new Application;
     * 
     * //set self router with TopNamespace
     * $app->setUserRouter('\AppTopNamespace\Router');
     * 
     * $app->run('\AppTopNamespace', '/path/AppPath');
     * </code>
     * 
     * define your websiet index page of root when router of toknot,
     * like this:
     * <code>
     * use Toknot\Control\Application;
     *
     * require_once './Toknot/Control/Application.php';
     *
     * $app = new Application;
     * 
     * //set index page without TopNamespace and ControllerNamespace
     * $app->run('\AppTopNamespace', '/path/AppPath', '\Index');
     * </code>
     * 
     * @param string $appNameSpace  Application of Namespace with top without full namespace
     * @param string $appPath   Application of directory with full path, and not is Controller layer full path
     * @param string $defaultInvoke  The parameter of default invoke class for router when no request uri,
     *                                if it is not set,will throw BadClassCallException when user request site root 
     *                                and no query,The class name of default with not full namespace
     *                                class name can not contain application top namespace and
     *                                Controller layer namespace
     * @param mixed $_   Variable list of router need of paramers on runtime, the toknot default router support
     *                    2 parameters:
     *                    $routerMode  use set {@see Router::ROUTER_PATH} or {@see Router::ROUTER_GET_QUERY}
     *                    $routerDepth it is controller max namespace level
     * @throws BadNamespaceException
     * @throws BadClassCallException
     * @throws StandardException
     */
    public function run($appNameSpace, $appPath, $defaultInvoke = '\Index') {
        $root = substr($appNameSpace, 0, 1);
        try {
            if ($root != '\\') {
                throw new BadNamespaceException($appNameSpace);
            }
            if (!class_exists($this->routerName, true)) {
                throw new BadClassCallException($this->routerName);
            }
            $routerReflection = new ReflectionClass($this->routerName);
            if (!$routerReflection->implementsInterface('Toknot\Control\RouterInterface')) {
                throw new StandardException('Router not support');
            }
            if ($routerReflection->hasMethod('singleton')) {
                $routerName = $this->routerName;
                $router = $routerName :: singleton();
            } else {
                $router = new $this->routerName;
            }

            $args = func_get_args();
            $this->addAppPath($appPath);
            $context = FMAI::singleton($appPath);
            call_user_func_array(array($router,'runtimeArgs'), array_slice($args,2));
            $router->routerSpace($appNameSpace);
            $router->routerPath($appPath);
            $router->routerRule();
            if(is_null($defaultInvoke)) {
                $root = substr($defaultInvoke, 0, 1);
                if ($root != '\\') {
                    throw new BadNamespaceException($defaultInvoke);
                }
                $router->defaultInvoke($defaultInvoke);
            }
            $router->invoke($context);
        } catch (StandardException $e) {
            echo $e;
        }
    }

    /**
     * transform php error to Exceptoion, all error will use {@link StandardException}
     * 
     * @access private
     */
    public function errorReportHandler() {
        $argv = func_get_args();
        StandardException::errorReportHandler($argv);
    }
    
    /**
     * set user router instead of toknot default router
     * 
     * @param string $routerName   The string of need of router name
     * @throws BadNamespaceException
     */
    public function setUserRouter($routerName) {
        $root = substr($routerName, 0, 1);
        if ($root != '\\') {
            throw new BadNamespaceException($routerName);
        }
        $this->routerName = $routerName;
    }
    
    /**
     * Append application path to autoloader scan path list
     * 
     * @param string $path  Full path of directory
     * @access private
     */
    private function addAppPath($path) {
        $this->standardAutoLoader->addPath($path);
    }
    
    /**
     * Register Autoloader Class
     */
    private function registerAutoLoader() {
        $this->standardAutoLoader = new StandardAutoloader();
        $this->standardAutoLoader->register();
    }

}

?>

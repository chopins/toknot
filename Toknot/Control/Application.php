<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

include_once __DIR__ . '/StandardAutoloader.php';

use Toknot\Control\StandardAutoloader;
use Toknot\Exception\StandardException;
use Toknot\Contorl\Exception\PHPVersionException;
use Toknot\Exception\BadNamespaceException;
use Toknot\Exception\BadClassCallException;
use Toknot\Control\FMAI;
use Toknot\Control\RouterInterface;
use Toknot\Di\TKFunction as TK;

/**
 * Toknot main class and run framework
 * 
 * <code>
 * //if development, define the constant set true, otherwise set false
 * //Note the constant default set true
 * define('DEVELOPMENT', true); 
 * 
 * use Toknot\Control\Application;
 * require_once '/path/Toknot/Control/Application.php';
 * $app = new Application;
 * $app->run('\AppTopNamespace', '/path/AppPath');
 * </code>
 * 
 */
final class Application {

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
     * App root path
     *
     * @access private
     * @static
     * @var string
     */
    private static $appRoot = '';

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
     * @access private
     * @var array
     */
    private $routerArgs = array();

    /**
     * @access private
     * @var array
     */
    private $debugTrace = array();

    /**
     * @access private
     * @var float
     */
    private $traceTime = 0;

    /**
     * @access private 
     * @var float
     */
    private $scriptStartTime = 0;

    /**
     * The construct parameters only receive PHP in CLI mode passed  argv and argc 
     * parameters and save there to $_SERVER via iniEnv method, the method has define
     * one constant of name {@see DEVELOPMENT} which is set true (default value) will show Exception
     * message, is false only return 500 Internal Server Error status code
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
        $this->scriptStartTime = microtime(true);
        //define Application status, DEVELOPMENT is true will show Exeption
        if (!defined('DEVELOPMENT')) {
            define('DEVELOPMENT', true);
        }
        
        StandardAutoloader::importToknotModule('Di', 'Object');
        StandardAutoloader::importToknotClass('Exception\StandardException');

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
            if (empty($argv)) {
                $_SERVER['argc'] = $GLOBALS['argc'];
                $_SERVER['argv'] = $GLOBALS['argv'];
            } else {
                $_SERVER['argc'] = $argc;
                $_SERVER['argv'] = $argv;
            }
        }

        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            throw new PHPVersionException();
        }
        StandardAutoloader::importToknotClass('Exception\StandardException');
        set_exception_handler(array($this, 'uncaughtExceptionHandler'));
        set_error_handler(array($this, 'errorReportHandler'));
        clearstatcache();

        if (DEVELOPMENT && self::checkXDebug() == false && function_exists('register_tick_function')) {
            error_reporting(0);
            register_shutdown_function(array($this, 'errorExitReportHandler'));
            declare (ticks = 1);
            register_tick_function(array($this, 'tickTraceHandler'));
        }
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
        if (!isset($_SERVER['QUERY_STRING'])) {
            $_SERVER['QUERY_STRING'] = getenv('QUERY_STRING');
        }
        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
        }

        if (strpos($variables_order, 'G') === false) {
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }
        $_SERVER['HEADERS_LIST'] = array();
        $_SERVER['COOKIES_LIST'] = array();
        if(empty($_SERVER['TK_SERVER'])) {
            $_SERVER['TK_SERVER'] = false;
        }
    }

    /**
     * The method set router need parameters,
     * the method can recived variable number of arguments, parameter info same your runtimeArgs()
     * method paramters of your Router, if default, {@see Toknot\Control\Router::runtimeArgs()} use 4 parameters 
     * that is $routerMode, $routerDepth, $notFound, $methodNotAllowed
     * 
     * @param mixed $*   Variable list of router need of paramers on runtime, the toknot default router support
     */
    public function setRouterArgs() {
        $this->routerArgs = func_get_args();
    }

    /**
     * Run application, the method will invoke router with implements interface of 
     * {@link Toknot\Control\RouterInterface} of all method, Toknot Freamework default
     * invoke class under application of Controller Dicetory, scan file path is under $appPath 
     * parameter set path(like: /path/appPath/Controller). The class be invoke by toknot of router 
     * invoke method with passed instance of Toknot 
     * {@see \Toknot\Control\FMAI}, you can receive the object of instance when class construct
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
     * if change router mode,
     * like this:
     * <code>
     * use Toknot\Control\Application;
     * use Toknot\Control\Router.php;
     * require_once './Toknot/Control/Application.php';
     *
     * $app = new Application;
     * 
     * //set index page without TopNamespace and ControllerNamespace
     * //set router mode is PATH query mode
     * //set router namespace level is 2
     * $app->setRouterArgs(Router::ROUTER_PATH, 2);
     * 
     * $app->run('\AppTopNamespace', '/path/AppPath', '\Index');
     * </code>
     * 
     * @param string $appNameSpace  Application of Namespace with top without 
     *                                  full namespace, the suffix without DIRECTORY_SEPARATOR
     * @param string $appPath   Application of directory with full path, 
     *                             and not is Controller layer full path
     * @param string $defaultInvoke  The parameter of default invoke class for 
     *                                   router when no request uri,
     *                                if it is not set,will throw BadClassCallException
     *                                   when user request site root and no query,
     *                                   The class name of default with not full namespace
     *                                class name can not contain application top namespace and
     *                                Controller layer namespace
     * @throws BadNamespaceException
     * @throws BadClassCallException
     * @throws StandardException
     */
    public function run($appNameSpace, $appPath, $defaultInvoke = '\Index') {

        $root = substr($appNameSpace, 0, 1);
        $appNameSpace = rtrim($appNameSpace, '\\');
        $appPath = rtrim($appPath, DIRECTORY_SEPARATOR);
        try {
            if ($root != '\\') {
                throw new BadNamespaceException($appNameSpace);
            }
            StandardAutoloader::importToknotClass('Control\RouterInterface');
            if ($this->routerName == '\Toknot\Control\Router') {
                StandardAutoloader::importToknotClass('Control\Router');
            }
            if (!class_exists($this->routerName, false)) {
                throw new BadClassCallException($this->routerName);
            }

            if ($this->routerName instanceof RouterInterface) {
                throw new StandardException('Router not support');
            }
            $this->addAppPath($appPath);
            self::$appRoot = $appPath;

            $routerName = $this->routerName;
            $router = new $routerName();
            $router->routerSpace($appNameSpace);
            $router->routerPath($appPath);

            StandardAutoloader::importToknotClass('Control\FMAI');
            $FMAI = FMAI::singleton($appNameSpace, $appPath);

            if (!empty($this->routerArgs)) {
                call_user_func_array(array($router, 'runtimeArgs'), $this->routerArgs);
            }
            $router->loadConfigure();
            $router->routerRule();
            if (is_null($defaultInvoke)) {
                $root = substr($defaultInvoke, 0, 1);
                if ($root != '\\') {
                    throw new BadNamespaceException($defaultInvoke);
                }
                $router->defaultInvoke($defaultInvoke);
            }

            $router->invoke($FMAI);
        } catch (StandardException $e) {
            if (PHP_SAPI == 'cli' && !is_resource(STDOUT)) {
                $e->save();
                return;
            }
            if (DEVELOPMENT) {
                echo $e;
            } else {
                Tk\header('500 Internal Server Error');
                echo ('500 Internal Server Error');
                return;
            }
        } catch (Exception $e) {
            if (PHP_SAPI == 'cli' && !is_resource(STDOUT)) {
                $e->save();
                return;
            }
            if (DEVELOPMENT) {
                echo $e;
            } else {
                TK\header('500 Internal Server Error');
                echo('500 Internal Server Error');
                return;
            }
        }
    }

    public function tickTraceHandler() {
        $testTrace = debug_backtrace();
        if (!isset($testTrace[1]['type']) || ($testTrace[1]['type'] != '->' && $testTrace[1]['type'] != '::')) {
            return;
        }
        $start = microtime(true);
        $this->debugTrace = $testTrace;
        $this->traceTime += microtime(true) - $start;
    }

    public function uncaughtExceptionHandler($e) {
        try {
            throw new StandardException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        } catch (StandardException $se) {
            if (PHP_SAPI == 'cli' && !is_resource(STDOUT)) {
                $se->save();
                return;
            }
            if (DEVELOPMENT) {
                $se->traceArr = $e->getTrace();
                echo $se;
            } else {
                TK\header('500 Internal Server Error');
                echo '500 Internal Server Error';
                return;
            }
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

    public function errorExitReportHandler() {
        $err = error_get_last();
        if (empty($err))
            return;
        if (in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING))) {
            try {
                throw new StandardException($err['message'], $err['type'], $err['file'], $err['line']);
            } catch (StandardException $e) {
                if (PHP_SAPI == 'cli' && !is_resource(STDOUT)) {
                    $e->save();
                    return;
                }
                if (DEVELOPMENT) {
                    array_shift($this->debugTrace);
                    $e->traceArr = $this->debugTrace;
                    echo $e;
                    $this->pageRunInfo();
                } else {
                    TK\header('500 Internal Server Error');
                    echo '500 Internal Server Error';
                    return;
                }
            }
        }
    }

    public static function getMemoryUsage() {
        $m = memory_get_usage(true);
        if ($m > 1024 * 1024) {
            return round($m / (1024 * 1024), 2) . ' MiB';
        } else if ($m > 1024) {
            return round($m / 1024, 2) . ' KiB';
        } else {
            return $m . " iB";
        }
    }

    public function pageRunInfo() {
        $mem = self::getMemoryUsage();
        if ($this->traceTime < 1) {
            $this->traceTime = round($this->traceTime * 1000, 2) . ' ms';
        } else {
            $this->traceTime .= ' seconds';
        }
        $str = '<br /><b style="color:red;">The trace time: ' . $this->traceTime . "</b>\n";
        $str .= '<br />Memory Usage: ' . $mem . "\n";
        $et = microtime(true) - $this->scriptStartTime;
        if ($et < 1) {
            $et = round($et * 1000, 2) . ' ms';
        } else {
            $et = $et . ' seconds';
        }
        $str .= '<br />PHP Script Execure Time: ' . $et . "\n";
        echo PHP_SAPI == 'cli' && is_resource(STDOUT) ? strip_tags($str) : $str;
    }

    public static function checkXDebug() {
        if (extension_loaded('xdebug') && ini_get('xdebug.default_enable') == 1) {
            return true;
        }
        return false;
    }

    public function __destruct() {
        if (DEVELOPMENT) {
            $this->pageRunInfo();
        }
    }

    public static function getAppRoot() {
        return self::$appRoot;
    }
    public static function newInstance() {
        return new static;
    }
}

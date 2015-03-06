<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Core;

include_once __DIR__ . '/Autoloader.php';

use Toknot\Core\Autoloader;
use Toknot\Core\Exception\PHPVersionException;
use Toknot\Core\Router;
use Toknot\Config\ConfigLoader;
use Toknot\Exception\BaseException;
use Toknot\Exception\BadNamespaceException;
use Toknot\Exception\BadClassCallException;

/**
 * Toknot main class and run framework
 * 
 * <code>
 * //if development, define the constant set true, otherwise set false
 * //Note the constant default set true
 * define('DEVELOPMENT', true); 
 * 
 * use Toknot\Core\Application;
 * require_once '/path/Toknot/Core/Application.php';
 * $app = new Application;
 * $app->run('\AppTopNamespace', '/path/AppPath');
 * </code>
 * 
 */
final class Application {

    /**
     * This save toknot of standard autoloader ({@see Toknot\Core\Autoloader}) instance
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
     * use Toknot\Core\Application;
     * 
     * require_once '/path/Toknot/Core/Application.php';
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
        Autoloader::importToknotModule('Core', 'Object');
        $this->registerAutoLoader();
        $this->initAppRootPath();
        $this->importConfig();
        
        date_default_timezone_set(self::timezoneString(ConfigLoader::CFG()->App->timeZone));

        $this->scriptStartTime = microtime(true);
        //define Application status, DEVELOPMENT is true will show Exeption
        if (!defined('DEVELOPMENT')) {
            define('DEVELOPMENT', true);
        }


        Autoloader::importToknotClass('Exception\BaseException');

        $this->iniEnv($argv, $argc);


        if (PHP_SAPI == 'cli' && basename($_SERVER['argv'][0]) == 'Toknot.php') {
            $this->runCLI();
        }
    }

    private function importConfig() {
        Autoloader::importToknotClass('Config\ConfigLoader');
        ConfigLoader::$cacheDir = FileObject::getRealPath(self::$appRoot, 'Data/Config');
        ConfigLoader::singleton();

        if (file_exists(self::$appRoot . '/Config/config.ini')) {
            ConfigLoader::importCfg(self::$appRoot . '/Config/config.ini');
        }
    }

    private function runCLI() {
        if (isset($_SERVER['argv'][1])) {
            $filename = __DIR__ . "/Command/{$_SERVER['argv'][1]}.php";
            if (file_exists($filename)) {
                include_once $filename;
                return new $_SERVER['argv'][1]($_SERVER['argv'], $_SERVER['argc']);
            }
        }
        echo "Undefined {$_SERVER['argv'][1]}";
        echo 'Usage: php Toknot.php command
            command :
                CreateApp           Create a application follow one by one
                GeneratePassword    Use current configure encrypt text
';

        exit;
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

        Autoloader::importToknotClass('Exception\BaseException');
        set_exception_handler(array($this, 'uncaughtExceptionHandler'));
        set_error_handler(array($this, 'errorReportHandler'));

        if (version_compare(PHP_VERSION, '5.3.0') < 0) {
            throw new PHPVersionException();
        }

        clearstatcache();

        if (DEVELOPMENT && self::checkXDebug() == false && function_exists('register_tick_function')) {
            error_reporting(0);
            register_shutdown_function(array($this, 'errorExitReportHandler'));
            declare (ticks = 1);
            register_tick_function(array($this, 'tickTraceHandler'));
        }
    }

    private function initAppRootPath() {
        self::$appRoot = dirname(dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
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
        if (empty($_SERVER['TK_SERVER'])) {
            $_SERVER['TK_SERVER'] = false;
        }
    }

    /**
     * Run application, the method will invoke router with implements interface of 
     * {@link Toknot\Core\RouterInterface} of all method, Toknot Freamework default
     * invoke class under application of Controller Dicetory, scan file path is under $appPath 
     * parameter set path(like: /path/appPath/Controller). The class be invoke by toknot of router 
     * invoke method, you can receive the object of instance when class construct
     * 
     * @throws BadNamespaceException
     * @throws BadClassCallException
     * @throws BaseException
     */
    public function run() {
        try {
            $appPath = self::$appRoot;
            $appNameSpace = ConfigLoader::CFG()->App->rootNamespace;
            $defaultInvoke = ConfigLoader::CFG()->App->defaultInvokeController;
            $root = substr($appNameSpace, 0, 1);
            $appNameSpace = rtrim($appNameSpace, Autoloader::NS_SEPARATOR);
            $appPath = rtrim($appPath, DIRECTORY_SEPARATOR);

            if ($root != Autoloader::NS_SEPARATOR) {
                throw new BadNamespaceException($appNameSpace);
            }

            $this->addAppPath($appPath);
            self::$appRoot = $appPath;

            $router = new Router();
            $router->routerSpace($appNameSpace);
            $router->routerPath($appPath);

            $router->loadConfigure();
            $router->routerRule();
            if (empty($defaultInvoke)) {
                $root = substr($defaultInvoke, 0, 1);
                if ($root != Autoloader::NS_SEPARATOR) {
                    throw new BadNamespaceException($defaultInvoke);
                }
                $router->defaultInvoke($defaultInvoke);
            }

            $router->invoke();
        } catch (BaseException $e) {
            if (PHP_SAPI == 'cli' && !is_resource(STDOUT)) {
                $e->save();
                return;
            }
            if (DEVELOPMENT) {
                echo $e->__toString();
            } else {
                header('500 Internal Server Error');
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
                header('500 Internal Server Error');
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
            throw new BaseException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        } catch (BaseException $se) {
            if (PHP_SAPI == 'cli' && !is_resource(STDOUT)) {
                $se->save();
                return;
            }
            if (DEVELOPMENT) {
                $se->traceArr = $e->getTrace();
                echo $se;
            } else {
                header('500 Internal Server Error');
                echo '500 Internal Server Error';
                return;
            }
        }
    }

    /**
     * transform php error to Exceptoion, all error will use {@link BaseException}
     * 
     * @access private
     */
    public function errorReportHandler() {
        $argv = func_get_args();
        if ($argv[0] == 2048 && strpos($argv[1], 'Declaration') === 0) {
            return;
        }
        BaseException::errorReportHandler($argv);
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
        $this->standardAutoLoader = new Autoloader();
        $this->standardAutoLoader->register();
    }

    public function errorExitReportHandler() {
        $err = error_get_last();
        if (empty($err))
            return;
        if (in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING))) {
            if($err['message'] == 'Cannot override final method Toknot\Core\Object::__construct()') {
                $err['message'] .= ', __init() is alternative to __construct';
            }
            try {
                throw new BaseException($err['message'], $err['type'], $err['file'], $err['line']);
            } catch (BaseException $e) {
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
                    header('500 Internal Server Error');
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
        if (empty($_SERVER['TK_DISABLE_OUTRUNINFO'])) {
            echo PHP_SAPI == 'cli' && is_resource(STDOUT) ? strip_tags($str) : $str;
        }
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

    public static function timezoneString($timezone) {
        if ($timezone[0] == '+') {
            $timedirection = '-';
        } elseif ($timezone[0] == '-') {
            $timedirection = '+';
        } elseif (is_numeric($timezone)) {
            $timedirection = '-';
            $offset = $timezone;
        } else {
            return $timezone;
        }
        if (empty($offset)) {
            $offset = substr($timezone, 1, 2);
            if (strlen($offset) == 2 && $offset[0] == '0') {
                $offset = substr($offset, 1);
            }
        }
        return "Etc/GMT{$timedirection}{$offset}";
    }

}

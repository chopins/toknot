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


class Application {

    private $standardAutoLoader = null;
    private $routerName = '\Toknot\Contorl\Router';
    public function __construct($argc = null, $argv = null) {
        $this->iniEnv($argc, $argv);
        $this->registerAutoLoader();
    }

    private function iniEnv($argc, $argv) {
        $this->checkSuperglobals();
        if (PHP_SAPI == 'cli' && !isset($_SERVER['argv'])) {
            $_SERVER['argc'] = $argc;
            $_SERVER['argv'] = $argv;
        }
        if(version_compare(PHP_VERSION,'5.3.0') < 0) {
            throw new PHPVersionException();
        }
        set_error_handler(array($this, 'errorReportHandler'));
        clearstatcache();
        
    }

    /**
     * check php superglobals whether be set or 
     * not will set $_SERVER,$_GET
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
        if(!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
        }
        if (strpos($variables_order, 'G') === false) {
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }
    }

    public function run($appNameSpace) {
        $root = substr($appNameSpace, 0,1);
        if($root != '\\') {
            throw new StandardException('Namespace Error');
        }
        $routerReflection = new \ReflectionClass($this->routerName);
        if($routerReflection->implementsInterface('RouterInterface') === false) {
            throw new StandardException();
        }
        if($routerReflection->hasMethod('singleton')) {
            $routerName = $this->routerName;
            $router = $routerName :: singletion();
        } else {
            $router = new $this->routerName;
        }
        $args = func_get_args();
        $router->runtimeArgs(array_shift($args));
        $router->routerSpace($appNameSpace);
        $router->routerRule();
        $router->invoke();
    }

    /**
     * transform php error to Exceptoion
     */
    public function errorReportHandler() {
        $argv = func_get_args();
        StandardException::errorReportHandler($argv);
    }

    public function setUserRouter($routerName) {
        $root = substr($routerName, 0,1);
        if($root != '\\') {
            throw new StandardException('Namespace Error');
        }
        $this->routerName = $routerName;
    }

    public function addAppPath($path) {
        $this->standardAutoLoader->addPath($path);
    }

    private function registerAutoLoader() {
        $this->standardAutoLoader = new StandardAutoloader();
        $this->standardAutoLoader->register();
    }

}

?>

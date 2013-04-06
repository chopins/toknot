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
use \Toknot\Http\FastCGIServer;

class Application {

    public $RUN_START_TIME = 0;
    private $standardAutoLoader = null;

    public function __construct($argc = null, $argv = null) {
        $this->iniEnv($argc, $argv);
        $this->registerAutoLoader($argc, $argv);
    }

    private function iniEnv($argc, $argv) {
        $this->RUN_START_TIME = microtime(true);
        define('PHP_CLI', PHP_SAPI == 'cli');
        if (PHP_CLI && !isset($_SERVER['argv'])) {
            $_SERVER['argc'] = $argc;
            $_SERVER['argv'] = $argv;
        }
        set_error_handler(array($this,'errorReportHandler'));
        clearstatcache();
    }
    public function errorReportHandler() {
        $argv = func_get_args();
        StandardException::errorReportHandler($argv);
    }

    public function runUserRouter($callback) {
        $argv = func_get_args();
        array_shift($argv);
        call_user_func_array($callback, $argv);
    }

    public function runDefaultRouter() {
        Router::singleton();
    }

    public function runCGIServer($callback) {
        try {
            return new FastCGIServer();
        } catch (StandardException $e) {
            echo $e;
        }
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

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

use Toknot\Control\StandardAutoLoader;

class Application {

    public $RUN_START_TIME = 0;
    private $standardAutoLoader = null;

    public function __construct($argc, $argv) {
        $this->iniEnv();
        $this->registerAutoLoader($argc, $argv);
        $this->run();
    }

    private function iniEnv($argc, $argv) {
        $this->RUN_START_TIME = microtime(true);
        if (PHP_SAPI == 'cli' && !isset($_SERVER['argv'])) {
            $_SERVER['argc'] = $argc;
            $_SERVER['argv'] = $argv;
        }
        defined('__TK_EXCEPTION_LEVEL__') || define('__TK_EXCEPTION_LEVEL__', 2);
        defined('__TK_NO_WEB_SERVER__') || define('__TK_NO_WEB_SERVER__', false);
        defined('__TK_DAEMON_LOOP_FILE__') || define('__TK_DAEMON_LOOP_FILE__', false);
        clearstatcache();
    }

    public function run() {
        \Toknot\Control\Router::singleton();
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

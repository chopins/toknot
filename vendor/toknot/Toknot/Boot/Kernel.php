<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 * @filesource
 * @package Toknot.Boot
 */

namespace Toknot\Boot;

use Toknot\Boot\Object;
use Toknot\Boot\Configuration;
use Toknot\Exception\BaseException;
use Toknot\Exception\ShutdownException;
use Toknot\Boot\GlobalFilter;
use Toknot\Boot\Pipe;
use Toknot\Boot\Logs;
use Toknot\Boot\Promise;
use Toknot\Boot\Decorator;
use Toknot\Boot\Tookit;

final class Kernel extends Object {

    use ObjectHelper;

    /**
     *
     * @var int 
     * @readonly
     */
    private $argc = 0;

    /**
     *
     * @var array
     * @readonly 
     */
    private $argv = [];

    /**
     *
     * @readonly
     */
    private $cfg;

    /**
     * @readonly
     */
    private $import;

    /**
     * @readonly
     */
    private $isCLI = false;
    private $cmdOption = [];
    private $confgType = 'ini';

    /**
     *
     * @readonly
     */
    private $schemes = '';
    private $displayTrace = true;
    private $logger = null;
    private $logEnable = false;

    /**
     *
     * @readonly
     */
    private $pid = 0;

    /**
     *
     * @readonly
     */
    private $tid = 0;
    private $runResult = [];
    private $shutdownFunction = null;

    /**
     *
     * @readonly
     */
    private $callInstance = null;
    private $callWrapper = '';
    private $wrapperList = [];

    /**
     *
     * @readonly
     */
    private $requestMethod = 'CLI';

    /**
     *
     * @readonly
     */
    private $requestUri = '';
    private $loggerClass = null;
    private $enableShortPath = false;
    private $defaultCall = null;

    /**
     *
     * @readonly
     */
    private $charset = 'utf-8';

    const PASS_STATE = 0;

    /**
     * 
     * @param array $argc
     * @param int $argv
     */
    protected function __construct($argc, $argv) {

        define('PHP_SP', ' ');
        $this->setArg($argc, $argv);
        $this->initGlobalEnv();

        if (PHP_SAPI == 'cli') {
            $this->isCLI = true;
            $this->console();
        }
        try {
            $this->runResult['option'] = [];
            $this->setResponse(self::PASS_STATE);

            set_error_handler($this->__callable()->errorReportHandler());

            set_exception_handler($this->__callable()->uncaughtExceptionHandler());

            register_shutdown_function($this->__callable()->__destruct());
        } catch (\Exception $e) {
            $this->echoException($e);
            $this->response();
            exit();
        } catch (\Error $e) {
            $this->echoException($e);
            $this->response();
        }
    }

    private function initGlobalEnv() {
        ini_set('html_errors', 0);
        ini_set('log_errors', 0);
        if (!ini_get('date.timezone')) {
            ini_set('date.timezone', 'UTC');
        }

        if (version_compare(PHP_VERSION, '5.4') < 0) {
            die('require php version >=5.4');
        }
        list($m, $r) = explode('.', PHP_VERSION);
        if ($m == 5) {
            define('PHP_MIN_VERSION', $r);
        } else {
            define('PHP_MIN_VERSION', $m);
        }


        $this->pid = getmypid();
        if (function_exists('zend_thread_id')) {
            $this->tid = zend_thread_id();
        }
    }

    public function propertySetList() {
        return ['displayTrace' => 'app.display_trace',
            'loggerClass' => 'app.log.logger',
            'logEnable' => 'app.log.enable',
            'logCfg' => 'app.log',
            'enableShortPath' => 'app.short_except_path',
            'wrapperList' => 'wrapper',
            'defaultCall' => 'app.default_call',
            'vendor' => 'vendor',
            'charset' => 'app.charset'];
    }

    private function setRuntimeEnv($parseClass = null) {
        if (!extension_loaded('filter')) {
            GlobalFilter::unavailablePHPFilter();
        }

        Configuration::setParseConfObject($parseClass);
        list($this->schemes) = GlobalFilter::env('SERVER_PROTOCOL', '/');
        $this->schemes = strtolower($this->schemes);
        $this->cfg = $this->loadMainConfig();

        $this->autoConfigProperty($this->propertySetList(), $this->cfg);

        if ($this->logEnable && is_subclass_of($this->loggerClass, 'Toknot\Boot\Logger')) {
            $this->logger = new $this->loggerClass($this->logCfg);
        } else {
            $this->logger = $this->loggerClass;
        }


        if ($this->enableShortPath) {
            Logs::$shortPath = true;
        }

        $this->requestMethod = GlobalFilter::env('REQUEST_METHOD');
        $this->requestUri = GlobalFilter::env('REQUEST_URI');

        $this->importVendor();
        $this->registerWrapper();
        $this->init();
    }

    public function registerWrapper() {
        foreach ($this->wrapperList as $cls) {
            if (is_subclass_of($cls, 'Toknot\Boot\SystemCallWrapper', true)) {
                $cls::register();
            } else {
                throw new BaseException("wrapper $cls must implements Toknot\Boot\SystemCallWrapper");
            }
        }
    }

    public function init() {
        $scheme = parse_url(ltrim($this->requestUri, '/'), PHP_URL_SCHEME);
        if (!$scheme) {
            $this->callWrapper = $this->wrapperList[$this->defaultCall];
        } else {
            foreach ($this->wrapperList as $pro => $cls) {
                if ($pro == $scheme) {
                    $this->callWrapper = $cls;
                    break;
                }
            }
        }

        $callWrapper = $this->callWrapper;
        $this->callInstance = self::invokeStatic($callWrapper, $callWrapper::__method()->getInstance);

        $this->callInstance->init($this->requestUri);
    }

    public function addResultOption($option) {
        $this->runResult[] = $option;
    }

    /**
     * 
     * @param string $configType        use config type
     * @param Toknot\Boot\ParseConfig $parseClass  set parse config class instance
     * @return int
     */
    public function run($configType, $parseClass = null) {
        $this->confgType = $configType;
        $this->setRuntimeEnv($parseClass);

        try {
            $this->callInstance->call();
        } catch (\Exception $e) {
            $this->echoException($e);
        } catch (\Error $e) {
            $this->echoException($e);
        }
        return $this->response();
    }

    public function call($path) {
        $scheme = parse_url($path, PHP_URL_SCHEME);
        $wrapperClass = $this->wrapperList[$scheme];
        $ins = self::invokeStatic($wrapperClass, $wrapperClass::__method()->getInstance);
        $ins->init($path);
        return $ins->call();
    }

    protected function response() {
        if (!$this->callInstance instanceof SystemCallWrapper) {
            echo $this->runResult['message'];
            echo $this->runResult['content'];
        } else {
            $this->callInstance->response($this->runResult);
        }
        return $this->runResult['code'];
    }

    public function shutdown() {
        throw new ShutdownException;
    }

    public function getResponse() {
        return $this->runResult;
    }

    public function isPassState() {
        return $this->runResult['code'] === self::PASS_STATE;
    }

    /**
     * 
     * @param int $status
     * @param string $message
     * @param string $content
     * @param string $option
     */
    public function setResponse($status = self::PASS_STATE, $message = '', $content = '', $option = []) {
        $this->runResult['code'] = $status;
        $this->runResult['message'] = $message;
        $this->runResult['content'] = $content;
        empty($option) || ($this->runResult['option'] = $option);
    }

    private function console() {
        $_SERVER['SERVER_PROTOCOL'] = 'cli';
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'CLI';
        $_SERVER['REQUEST_URI'] = '/' . str_replace('.', '/', Tookit::coalesce($this->argv, 1));
    }

    public function echoException($e) {
        if ($e instanceof ShutdownException) {
            return;
        }

        try {
            throw new BaseException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        } catch (BaseException $se) {
            $this->runResult = [];
            $this->runResult['code'] = $this->displayTrace ? 200 : ($e instanceof BaseException ? $e->getHttpCode() : 500);
            $this->runResult['message'] = $this->displayTrace ? 'OK' : ($e instanceof BaseException ? $e->getHttpMessage() : 'Internal Server Error');
            $trace = $se->getDebugTraceAsString();
            $this->runResult['content'] = $this->displayTrace ? $trace : '';
            $this->addResultOption("Content-type: text/html; charset={$this->charset}");
            if ($this->logEnable) {
                Logs::save($se->getDebugTraceAsString(true), $this->logger);
            }
            //$this->runResult['option'][] = '';
        }
    }

    private function setArg($argc, $argv) {
        $GLOBALS['argc'] = 0;
        $GLOBALS['argv'] = [];
        if (PHP_SAPI === 'cli') {
            $this->argc = $argc;
            $this->argv = $argv;
        }
    }

    private function walkOption() {
        $shortParam = $longParam = false;
        $option = [];
        foreach ($this->argv as $idx => $arg) {
            if (strpos($arg, '--') === 0) {
                $par = explode('=', $arg);
                $option[$par[0]] = count($par) == 2 ? $par[1] : '';
                $shortParam = false;
                $longParam = true;
            } elseif (strpos($arg, '-') === 0) {
                $shortParam = $arg;
                if (strlen($arg) > 2) {
                    $arg = substr($arg, 1, 1);
                    $option[$arg] = substr($arg, 2);
                } else {
                    $option[$arg] = '';
                }
            } elseif ($shortParam) {
                $option[$shortParam] = $arg;
                $shortParam = false;
            } elseif ($longParam && !$option[$par[0]]) {
                $option[$par[0]] = $arg;
                $longParam = false;
            } else {
                $option[$idx] = $arg;
            }
        }
        return $option;
    }

    /**
     * Get option of command line
     * 
     * @param string $key
     * @return string
     */
    public function getArg($key = null) {
        if ($this->callInstance && ($arg = $this->callInstance->getArg($key))) {
            return $arg;
        }
        if (empty($this->cmdOption)) {
            $this->cmdOption = $this->walkOption();
        }
        if ($key !== null) {
            return Tookit::coalesce($this->cmdOption, $key, '');
        } else {
            return $this->cmdOption;
        }
    }

    /**
     * Check a key wheter in option of command line 
     * 
     * @param string $key
     * @return boolean
     */
    public function hasOption($key) {
        if ($this->callInstance && ($arg = $this->callInstance->getArg($key))) {
            return $arg;
        }
        if (empty($this->cmdOption)) {
            $this->cmdOption = $this->walkOption();
        }
        return isset($this->cmdOption[$key]);
    }

    public function setImport($import) {
        $this->import = $import;
    }

    private function importVendor() {
        $vendor = dirname(TKROOT);

        foreach ($this->vendor as $v) {
            $this->import->addPath("$vendor/$v");
        }
        $appname = ucfirst(basename(APPDIR));
        $this->import->addPath(APPDIR . "/$appname");
    }

    private function loadMainConfig() {
        $ini = APPDIR . "/config/config.{$this->confgType}";
        return Configuration::loadConfig($ini);
    }

    public function config($key) {
        return $this->cfg->find($key);
    }

    /**
     * transform php error to Exceptoion, all error will use {@link BaseException}
     * 
     * @access private
     */
    public function errorReportHandler() {
        $argv = func_get_args();
        throw BaseException::errorReportHandler($argv);
    }

    public function uncaughtExceptionHandler($e) {
        $this->echoException($e);
        $this->response();
    }

    public function __destruct() {
        $this->releaseShutdownHandler();
    }

    /**
     * like posix pipe run program, 
     * 
     * argv of next call pass from previous call return, only first call need pass argv,
     * other call if pass arg will start new pipe
     * will start new pipe
     * 
     * <code>
     * $c = $this->pipe()->callable1($arg)->callable2()->callable3()->result();
     * //above code same below
     * $a = callable1($arg); 
     * $b = callable2($a); 
     * $c = callable3($b); 
     * 
     * $c = $this->pipe($obj)->callable()->callable2()->result();
     * //above code same below
     * $a = $obj->callable1();
     * $c = $obj->callable2($a);
     * 
     * $c = $this->pipe($obj)->callable()->callable2()()
     *
     * </code>
     * 
     * @param callable $callable
     * @param array $argv
     * @return Toknot\Boot\Pipe
     */
    public function pipe($cxt = null) {
        return new Pipe($cxt);
    }

    /**
     * start a promise
     * 
     * @param mix $passState
     * @param mix $elseState
     * @param object $cxt
     * @return Promise
     */
    public function promise($passState = true, $elseState = false, $cxt = null) {
        return new Promise($passState, $elseState, $cxt);
    }

    /**
     * decorate a function or class
     * 
     * @param callable $func
     * @param boolean $isClass
     * @return Decorator
     */
    public function decorator($func, $isClass = false) {
        return new Decorator($func, $isClass);
    }

    public function attachShutdownFunction($callable) {
        if (!$this->shutdownFunction instanceof \SplObjectStorage) {
            $this->shutdownFunction = new \SplObjectStorage;
        }
        $this->shutdownFunction->attach($callable);
    }

    public function releaseShutdownHandler() {
        if ($this->shutdownFunction instanceof \SplObjectStorage) {
            foreach ($this->shutdownFunction as $func) {
                $func();
                $this->shutdownFunction->detach($func);
            }
        }
    }

}

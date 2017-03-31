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

use Toknot\Boot\Tookit;
use Toknot\Boot\Import;
use Toknot\Boot\Object;
use Toknot\Boot\Configuration;
use Toknot\Exception\BaseException;
use Toknot\Exception\ShutdownException;
use Toknot\Boot\GlobalFilter;
use Toknot\Boot\Pipe;
use Toknot\Boot\Logs;
use Toknot\Boot\Promise;

final class Kernel extends Object {

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
    private $call = [];

    /**
     *
     * @readonly
     */
    private $schemes = '';
    private $trace = true;
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

    /**
     *
     * @readonly
     */
    private $request;
    private $runResult = [];
    private $shutdownFunction = null;

    /**
     *
     * @readonly
     */
    private $requestMethod = 'CLI';

    const PASS_STATE = 0;

    /**
     *
     * @var \Toknot\Share\Request
     * @access public
     * @readonly
     * @after Kernel::router()
     */

    /**
     * 
     * @param array $argc
     * @param int $argv
     */
    protected function __construct($argc, $argv) {
        define('PHP_NS', '\\');
        $this->setArg($argc, $argv);
        $this->initGlobalEnv();

        $this->initImport();

        if (PHP_SAPI == 'cli') {
            $this->isCLI = true;
            $this->console();
        }
        try {
            $this->runResult['option'] = [];
            $this->setResponse(self::PASS_STATE);

            set_error_handler(array($this, 'errorReportHandler'));
            set_exception_handler(array($this, 'uncaughtExceptionHandler'));
            register_shutdown_function(array($this, '__destruct'));
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

    private function setRuntimeEnv($parseClass = null) {
        if (!extension_loaded('filter')) {
            GlobalFilter::unavailablePHPFilter();
        }

        Configuration::setParseConfObject($parseClass);
        list($this->schemes) = GlobalFilter::env('SERVER_PROTOCOL', '/');
        $this->schemes = strtolower($this->schemes);
        $this->cfg = $this->loadMainConfig();

        $this->trace = $this->cfg->find('app.trace');

        $loggerClass = $this->cfg->find('app.log.logger');

        $this->logEnable = $this->cfg->find('app.log.enable');
        if ($this->logEnable && is_subclass_of($loggerClass, 'Toknot\Boot\Logger')) {
            $this->logger = new $loggerClass($this->cfg->find('app.log'));
        } else {
            $this->logger = $this->cfg->find('app.log.logger');
        }


        if ($this->cfg->find('app.short_except_path')) {
            Logs::$shortPath = strlen(dirname(dirname(TKROOT)));
        }
        $this->importVendor();
        $this->initRouter();
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
            $this->router();
        } catch (\Exception $e) {
            $this->echoException($e);
        } catch (\Error $e) {
            $this->echoException($e);
        }
        return $this->response();
    }

    protected function response() {
        if ($this->isCLI) {
            echo $this->runResult['content'];
            exit($this->runResult['code']);
        }
        header($this->runResult['message'], true, $this->runResult['code']);
        if (!empty($this->runResult['option'])) {
            foreach ($this->runResult['option'] as $op) {
                header($op);
            }
        } else {
            echo $this->runResult['content'];
        }
        return $this->runResult['code'];
    }

    public function shutdown() {
        throw new ShutdownException;
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

    /**
     * 
     * @return \Toknot\Boot\Route
     * @throws BaseException
     */
    public function routerIns() {
        $routerClass = $this->cfg->app->router;
        if (is_subclass_of($routerClass, 'Toknot\Boot\Route')) {
            return $routerClass::single();
        }
        throw new BaseException("$routerClass must implements Toknot\Boot\Route");
    }

    private function initRouter() {
        $this->routerIns()->load();
    }

    private function console() {
        $_SERVER['SERVER_PROTOCOL'] = 'cli';
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $_SERVER['REQUEST_METHOD'] = 'CLI';
        if ($this->argc < 2) {
            return;
        }
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $_SERVER['REQUEST_URI'] = '/' . str_replace('.', '/', $this->argv[1]);
    }

    private function launch($parameters, $ns, $type, $requireParams) {
        if (empty($parameters[$type])) {
            return false;
        }

        if (is_array($parameters[$type])) {
            foreach ($parameters[$type] as $name) {
                if (empty($name)) {
                    continue;
                }
                $class = Tookit::nsJoin($ns, $name);
                $this->call[$type] = $name;
                $this->invoke($class, $requireParams);
            }
        } else {
            $class = Tookit::nsJoin($ns, $parameters[$type]);
            $this->call[$type] = $parameters[$type];
            $this->invoke($class, $requireParams);
        }
    }

    private function invoke($call, $requireParams) {
        if ($this->runResult['code'] !== self::PASS_STATE) {
            return false;
        }

        $calls = explode('::', $call);
        $class = $calls[0];
        $paramsCount = $requireParams->count();

        $params = iterator_to_array($requireParams, false);
        if ($paramsCount > 0) {
            $groupins = self::constructArgs($paramsCount, $params, $class);
        } else {
            $groupins = new $class();
        }

        if (isset($calls[1])) {
            if ($paramsCount > 0) {
                self::callMethod($paramsCount, $calls[1], $params, $groupins);
            } else {
                $groupins->{$calls[1]}();
            }
        }
    }

    private function router() {
        $parameters = $this->routerIns()->match();
        $appCfg = $this->cfg->app;

        $this->request = $this->routerIns()->getRequest();
        $requireParams = $this->request->attributes;
        $exec = $this->routerIns()->middlewareNamespace($appCfg);
        foreach ($exec as $key => $ns) {
            $this->launch($parameters, $ns, $key, $requireParams);
        }
    }

    public function echoException($e) {
        if ($e instanceof ShutdownException) {
            return;
        }

        try {
            throw new BaseException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        } catch (BaseException $se) {
            $this->runResult = [];
            $this->runResult['code'] = $e instanceof BaseException ? $e->getHttpCode() : 500;
            $this->runResult['message'] = $e instanceof BaseException ? $e->getHttpMessage() : 'Internal Server Error';
            $trace = $se->getDebugTraceAsString();
            $this->runResult['content'] = $this->trace ? $trace : '';

            if ($this->logEnable) {
                Logs::save($this->logger, $trace);
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
    public function getOption($key = null) {
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
        if (empty($this->cmdOption)) {
            $this->cmdOption = $this->walkOption();
        }
        return isset($this->cmdOption[$key]);
    }

    private function initImport() {
        include __DIR__ . '/Import.php';
        $this->import = new Import();
        $this->import->register();
    }

    private function importVendor() {
        $vendor = dirname(TKROOT);

        foreach ($this->cfg->vendor as $v) {
            $this->import->addPath("$vendor/$v");
        }
        $appname = ucfirst(basename(APPDIR));
        $this->import->addPath(APPDIR . "/$appname");
    }

    public function __get($name) {
        if ($this->__isReadonlyProperty($name)) {
            return $this->{$name};
        }
        throw new BaseException("undefined property Kernel::\${$name}");
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
        self::releaseShutdownHandler();
    }

    /**
     * like posix pipe run program, 
     * 
     * argv of next call pass from previous call return, only first call need pass argv,
     * other call if pass arg will start new pipe
     * will start new pipe
     * 
     * <code>
     * $c = $this->pipe('callable1',$arg)->callable2()->callable3()->result();
     * //above code same below
     * $a = callable1($arg); 
     * $b = callable2($a); 
     * $c = callable3($b); 
     * 
     * $c = $this->pipe('callable1',$arg)->callable2($obj)->callable3($obj2)->result();
     * $a = callable1($arg);
     * $b = $obj->callable2($a);
     * $c = $obj2->callable3($b);
     *
     * </code>
     * 
     * @param callable $callable
     * @param array $argv
     * @return Toknot\Boot\Pipe
     */
    public function pipe($callable, $argv = []) {
        return new Pipe($callable, $argv);
    }

    /**
     * 
     * @param mix $passState
     * @param mix $elseState
     * @param object $cxt
     * @return Promise
     */
    public function promise($passState = true, $elseState = false, $cxt = null) {
        return new Promise($passState, $elseState, $cxt);
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

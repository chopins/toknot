<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Boot;

use Toknot\Boot\Controller;
use Toknot\Lib\Flag\Flag;
use Toknot\Boot\Logger;
use Toknot\Boot\Configuration;
use Toknot\Lib\Exception\ErrorReportHandler;

/**
 * @property-read string $root              Toknot framework root directory   
 * @property-read string $configFile        config file directory
 * @property-read string $dataPath          the  data directory
 * @property-read string $logDir            the log file directory
 * @property-read string $databasePath      the database structre file directory
 * @property-read string $runtime           the runtime data direcotory
 * @property-read Logger $logger            the logger instance
 * @property-read string $appPath           the app directory
 * @property-read Kernel $selfInstance      Kernel instance
 * @property-read Configuration $config     Configuration instance
 * @property-read string $serverEntropy     environment background entropy string
 * @property-read string $configHash        configuration file hash string
 * @property-read bool $enableTokenString   whether enable token stirng
 * @property-read string $releaseStatus     app version status
 * @property-read bool $shutdown            shutdown status
 */
class Kernel {

    private $root;
    private $configFile = '';
    private $dataPath = '';
    private $logDir = '';
    private $databasePath = '';
    private $runtime = '';
    private $logger = null;
    private $appPath = '';

    /**
     *
     * @var \Toknot\Boot\Kernel 
     */
    private static $selfInstance = null;

    private static $config = null;
    private static $serverEntropy = self::NOP;
    private static $configHash = '';
    public static $enableTokenString = true;
    public static $releaseStatus = 'dev';
    public static $shutdown = false;

    const NS = '\\';
    const AT = '@';
    const TOKNOT_NS = 'Toknot';
    const PHP_EXT = '.php';
    const DOLAR = '$';
    const NOP = '';
    const SP = ' ';
    const URL_SEP = '/';
    const HZL = '-';
    const UDL = '_';
    const ACTION = 'Action';
    const QUTM = '?';
    const EOL = PHP_EOL;
    const PATH_SEP = DIRECTORY_SEPARATOR;
    const LF = "\n";
    const CRLF = "\r\n";
    const CMLS = '::';
    const COLON = ':';
    const THEN = 'then';
    const COMMA = ',';
    const BACKTICK = '`';
    const QUOTE = '\'';
    const SEMI = ';';
    const STAR = '*';
    const PERCENT = '%';
    const EQ = '=';
    const LT = '<';
    const GT = '>';
    const LE = '<=';
    const GE = '>=';
    const NEQ = '!=';
    const LG = '<>';
    const LP = '(';
    const RP = ')';
    const LB = '{';
    const RB = '}';
    const L_AND = 'AND';
    const L_OR = 'OR';
    const L_XOR = 'XOR';
    const L_NOT = 'NOT';
    const M_ADD = '+';
    const M_SUB = '-';
    const M_MUL = '*';
    const M_DIV = '/';
    const DOT = '.';
    const DEF_CLASS = 'class ';
    const DEF_EXTENDS = ' extends ';
    const DEF_PROTECTED = 'protected ';
    const DEF_NS = 'namespace ';
    const DEF_USE = 'use ';
    const PAD_NO = -1;
    const PAD_LEFT = STR_PAD_LEFT;
    const PAD_RIGHT = STR_PAD_RIGHT;
    const PAD_BOTH = STR_PAD_BOTH;
    const BROWSER_ID = '_BROWSER_ID';
    const T_NUMBER = 'number';
    const T_STRING = 'string';
    const CLI = 'cli';
    const PHP = '<?php ';
    const R_DEV = 'dev';
    const R_ALPHA = 'alpha';
    const R_BETA = 'beta';
    const R_RC = 'rc';
    const R_RELEASE = 'release';
    const HEX = '0x';

    protected function __construct() {
        $this->initPath();
        $this->registerAutoload();
        $this->uncaughtException();
        $this->error2Exception();
        $this->config();
    }

    /**
     * get platform end of line char
     * 
     * @return string
     */
    public static function getEOLToken() {
        if (PHP_EOL === "\n") {
            return '\n';
        } elseif (PHP_EOL === "\r\n") {
            return '\r\n';
        } elseif (PHP_EOL === "\r") {
            return '\r';
        }
    }

    /**
     * get specify length hex string
     * 
     * @param int $length
     * @return string
     */
    public static function randHex($length) {
        if ($length % 2 !== 0) {
            self::runtimeException('random string length only even number', E_USER_WARNING);
        } elseif ($length < 2) {
            self::runtimeException('random string length must greater than 2', E_USER_WARNING);
        }
        $bytesLen = $length / 2;
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($bytesLen));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($bytesLen));
        } elseif (self::PATH_SEP === self::URL_SEP && is_readable('/dev/urandom') && ($fp = @fopen('/dev/urandom', 'rb'))) {
            $st = fstat($fp);
            if (($st['mode'] & 0170000) === 020000) {
                stream_set_read_buffer($fp, $bytesLen);
                $read = fread($fp, $bytesLen);
                return bin2hex($read);
            }
        } elseif (version_compare(PHP_VERSION, '7.2', '<') && function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($bytesLen / 2, MCRYPT_RAND));
        } elseif (class_exists('\COM', false) && ($com = new \COM('CAPICOM.Utilities.1')) && (method_exists($com, 'GetRandom'))) {
            $buf = '';
            $execCount = 0;
            do {
                $buf .= base64_decode((string) $com->GetRandom($length, 0));
                if (strlen($buf) >= $length) {
                    return bin2hex(substr($buf, 0, $length));
                }
                $execCount++;
            } while ($execCount < $length);
        }
        $base = str_pad('1234567890abcdef', $length * 2);
        return substr(str_shuffle($base), 0, $length);
    }

    /**
     * get 4 bit random integer
     * 
     * @param bool $signed
     * @return number
     */
    public static function randInt4($signed = false) {
        $number = gmp_strval(self::HEX . self::randHex(8));
        if ($signed && gmp_cmp($number, PHP_INT_MAX)) {
            return gmp_strval(gmp_div_q($number, 2));
        } elseif ($number) {
            return $number;
        }
    }

    /**
     * print a variable
     * 
     * @param mixed $value
     */
    public static function dump($value) {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        if (isset($stack[1]['function']) && $stack[1]['function'] == 'tk_dump') {
            $dumpStack = $stack[1];
        } else {
            $dumpStack = $stack[0];
        }
        if (PHP_SAPI !== self::CLI) {
            echo '<pre style="color:red;">';
        }
        if (strpos($dumpStack['file'], self::getInstance()->root) === 0) {
            $dumpStack['file'] = '.' . substr($dumpStack['file'], strlen(self::getInstance()->root));
        }
        echo "Dump in {$dumpStack['file']}({$dumpStack['line']}):";
        var_dump($value);
        if (PHP_SAPI !== self::CLI) {
            echo '</pre>';
        }
    }

    /**
     * get 8 bit random integer
     * 
     * @param bool $signed
     * @return number
     */
    public static function randInt8($signed = false) {
        $number = gmp_strval(self::HEX . self::randHex(16));
        if ($signed && gmp_cmp($number, '9223372036854775807')) {
            return gmp_strval(gmp_div_q($number, 2));
        } elseif ($number) {
            return $number;
        }
    }

    /**
     * get $GLOBALS array element value
     * 
     * @param string $key
     * @return mixed
     */
    public static function globals($key) {
        return $GLOBALS[$key];
    }

    /**
     * set current release version status
     * 
     * @param string $status
     */
    public static function release($status) {
        self::$releaseStatus = $status;
    }

    /**
     * get current release version status
     * 
     * @return string
     */
    public static function getRelaseStatus() {
        return self::$releaseStatus;
    }

    /**
     * get all available release version status name
     * 
     * @return array
     */
    public static function allRelaseStatusList() {
        return [self::R_DEV, self::R_ALPHA, self::R_BETA, self::R_RC, self::R_RELEASE];
    }

    /**
     * get local host ip
     * 
     * @return string
     */
    public static function localIp() {
        if (PHP_OS === 'Linux' && PHP_SAPI === self::CLI) {
            return exec('hostname --all-ip-addresses');
        } elseif (PHP_SAPI === self::CLI) {
            return gethostbyname(gethostname());
        }
        return $_SERVER['SERVER_ADDR'];
    }

    /**
     * get request ip
     * 
     * @return string
     */
    public function requestIp() {
        if (PHP_SAPI === self::CLI) {
            return self::localIp();
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * 
     * @param string $name
     * @return mix
     */
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return $name;
    }

    /**
     * get configuration data
     * 
     * @return Configuration
     */
    public function config() {
        if (self::$config === null) {
            self::$config = $this->readConfigure();
        }
        return self::$config;
    }

    protected function readConfigure() {
        $confg = json_decode(file_get_contents($this->configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::runtimeException(json_last_error_msg(), E_USER_ERROR);
        }
        $releaseConfig = $this->configFile . self::DOT . self::$releaseStatus;
        if (file_exists($releaseConfig)) {
            $relaseConfig = json_decode(file_get_contents($releaseConfig), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                self::runtimeException(json_last_error_msg(), E_USER_ERROR);
            }
            $confg = array_merge_recursive($confg, $relaseConfig);
        }

        return new Configuration($confg, Configuration::ARRAY_AS_PROPS);
    }

    /**
     * merge array or object, return value of type same the first param
     * if the parameter is object, only merge the object public property value
     * if the last parameter is bool type to denote whether recursive merge
     * 
     * @param array|object $first
     * @param array|object $data
     * @return array|object
     */
    public static function merge($first, ...$data) {
        if (!is_object($first) && !is_array($first)) {
            self::runtimeException("Argument #1 is not an array or object", E_USER_WARNING);
        }

        $argc = count($data);
        if ($argc < 1) {
            return $first;
        }
        $last = $argc - 1;
        $recursive = false;
        if (is_bool($data[$last]) === true) {
            $recursive = $data[$last];
            array_pop($data);
        }
        $firstIsArr = is_array($first);
        foreach ($data as $k => $d) {
            if (!is_object($d) && !is_array($d)) {
                $offset = $k + 1;
                self::runtimeException("Argument #{$offset} is not an array or object", E_USER_WARNING);
            }
            self::mergeData($first, $firstIsArr, $d);
        }
        return $first;
    }

    protected static function mergeData(&$first, $firstIsArr, $seoncd, $recursive = false) {
        foreach ($seoncd as $p => $v) {
            $override = false;
            switch (true) {
                case (!$recursive || (!is_object($v) && !is_array($v)) || (!isset($first[$p]) && !isset($first->$p))):
                case ($firstIsArr && isset($first[$p]) && (!is_object($first[$p]) && !is_array($first[$p]))):
                case (isset($first->$p) && (!is_object($first->$p) && !is_array($first->$p))):
                    $override = true;
            }
            if ($override && $firstIsArr) {
                $first[$p] = $v;
            } elseif ($override) {
                $first->$p = $v;
            } elseif ($firstIsArr) {
                self::mergeData($first[$p], is_array($first[$p]), $v);
            } else {
                self::mergeData($first->$p, is_array($first->$p), $v);
            }
        }
    }

    public function error2Exception() {
        set_error_handler(function(...$errData) {
            $handle = new ErrorReportHandler($errData);
            return $handle->throwException();
        });
    }

    /**
     * trigger eror and it add 'RuntimeException' prefix
     * 
     * @param string $message
     * @param int $type
     */
    public static function runtimeException($message, $type = E_USER_NOTICE) {
        trigger_error("RuntimeException: $message", $type);
    }

    public function uncaughtException() {
        set_exception_handler(function($e) {
            $this->echoException($e);
        });
    }

    /**
     * get or set app path
     * 
     * @param string $app
     * @return string
     */
    public function appRoot($app = '') {
        if ($app) {
            $this->appPath = $app;
        } else {
            $this->appPath = $this->root . self::PATH_SEP . 'App';
        }
        return $this->appPath;
    }

    protected function initPath() {
        $this->root = dirname(__DIR__);
        $this->configFile = $this->root . '/config/config.json';
        $this->dataPath = $this->root . '/data';
        self::mkdir($this->dataPath);
        $this->databasePath = $this->dataPath . '/database';
        self::mkdir($this->databasePath);
        $this->runtime = $this->dataPath . '/runtime';
        self::mkdir($this->runtime);
        $this->logDir = $this->dataPath . '/log';
        self::mkdir($this->logDir);
    }

    public function boot() {
        $this->logger = new Logger;
        try {
            Controller::response();
        } catch (\Exception $e) {
            $this->errorLogger($e);
        } catch (\Error $e) {
            $this->errorLogger($e);
        }
        Controller::exitCode();
        self::$shutdown = true;
    }

    /**
     * save log
     * 
     * @param int $level
     * @param string $message
     * @param array $data
     */
    public function logger($level, $message, $data = []) {
        $this->logger->log($level, $message, $data);
    }

    public function echoException($e) {
        Controller::pushException($e);
    }

    protected function errorLogger($e) {
        $args = [E_USER_ERROR, $e->getMessage(), $e->getFile(), $e->getLine()];
        $handler = new ErrorReportHandler($args);
        if ($handler->levelLogger($e)) {
            return;
        }
        $this->echoException($e);
    }

    protected function registerAutoload() {
        spl_autoload_register(function($class) {
            $file = $this->getToknotClassPath($class);
            if ($file && file_exists($file)) {
                return include $file;
            }
            $source = $this->isTests($class) ? 'tests' : 'src';
            $classPart = explode(self::NS, $class, 3);
            if (count($classPart) < 3) {
                return false;
            }
            $file4 = $this->getPsr4ClassPath($classPart, $source);
            if ($file4 && file_exists($file4)) {
                return include $file4;
            }
            $file0 = $this->getPsr0Path($classPart, $class, $source);
            if ($file0 && file_exists($file0)) {
                return include $file0;
            }
        });
    }

    /**
     * get path this namespace is begin Toknot of class 
     * 
     * @param string $class
     * @param bool $hasExt
     * @return boolean
     */
    public function getToknotClassPath($class, $hasExt = true) {
        if (strpos($class, self::TOKNOT_NS) === 0) {
            $notop = substr($class, strlen(self::TOKNOT_NS));
            $prefixPath = $this->root;
        } else {
            $nspart = explode(self::NS, $class, 2);
            if (count($nspart) < 2) {
                return false;
            }
            list($top, $notop) = $nspart;
            $prefixPath = $this->root . 'vendor/' . strtolower($top) . '/src';
        }
        return $this->convertClass2AppPath($prefixPath, $notop, $hasExt);
    }

    protected function convertClass2AppPath($prefixPath, $class, $hasExt = true) {
        $relativePath = ltrim(str_replace(self::NS, self::PATH_SEP, $class), self::PATH_SEP);
        return $prefixPath . self::PATH_SEP . $relativePath . ($hasExt ? self::PHP_EXT : '');
    }

    protected function isTests($class) {
        return substr($class, -4) === 'Test';
    }

    protected function getPsr4ClassPath($classPart, $source) {
        list($vendorName, $package, $className) = $classPart;
        $prefixPath = $this->root . 'vendor/' . strtolower($vendorName) . self::PATH_SEP . strtolower($package) . "/$source";
        return $this->convertClass2AppPath($prefixPath, $className);
    }

    protected function getPsr0Path($classPart, $class, $source) {
        list($vendorName, $package) = $classPart;
        $prefixPath = $this->root . 'vendor/' . strtolower($vendorName) . self::PATH_SEP . strtolower($package) . "/$source";
        return $this->convertClass2AppPath($prefixPath, $class);
    }

    /**
     * create new dir
     * 
     * @param type $dir
     * @param type $mode
     */
    public static function mkdir($dir, $mode = 0777, $recursive = false) {
        if (!is_dir($dir)) {
            mkdir($dir, $mode, $recursive);
        }
    }

    /**
     * get kernel class instance, if no instance will create new instance
     * 
     * @return \Toknot\Boot\Kernel
     */
    public static function instance() {
        if (self::$selfInstance === null) {
            self::$selfInstance = new static;
        }
        return self::$selfInstance;
    }

    /**
     * get exists kernel class instance
     * 
     * @return \Toknot\Boot\Kernel
     */
    public static function getInstance() {
        return self::$selfInstance;
    }

    public static function isBitSet($value, $bit) {
        return ($value & $bit ) === $bit;
    }

    public static function callFlagClass($name, $p = null) {
        $is = false;
        if (substr($name, 0, 2) == 'is') {
            $callClass = substr($name, 2);
            $is = true;
        } else {
            $callClass = substr($name, 2);
        }
        $class = substr(Flag::class, 0, -4) . $callClass;
        if ($is) {
            return $class :: isme($p);
        }
        return new $class;
    }

    public static function isFlag($p) {
        return Flag::isme($p);
    }

    /**
     * support static method: onDenide onNo onSkip onUnfound onYes
     *                        isDenide isNo isSkip isUnfound isYes
     *                        logEmergency logAlert logCritical logError logWarning logNotice logInfo logDebug
     * 
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws \BadMethodCallException
     */
    public static function __callStatic($name, $params = []) {
        $prefix = substr($name, 0, 2);
        $hasMethod = method_exists(__CLASS__, $name);
        if (!$hasMethod && ($prefix === 'is' || $prefix === 'on')) {
            return self::callFlagClass($name, isset($params[0]) ? $params[0] : null);
        } elseif (!$hasMethod && substr($name, 0, 3) === 'log') {
            $method = strtolower(substr($name, 3));
            return call_user_func_array([self::instance()->logger, $method], $params);
        }
        throw new \BadMethodCallException($name);
    }

    /**
     * convert string to uppercase use specify boundary string separator characters
     * different ucwords, the function will remove separator string
     * 
     * @param type $str     passed string
     * @param type $sep     boundary string
     * @return string
     */
    public static function toUpper($str, $sep = self::HZL) {
        return str_replace($sep, self::NOP, ucwords($str, $sep));
    }

    /**
     * convert class to lowercase use specify boundary string separator characters
     * 
     * @param string $str    specify class name
     * @param string $sep    boundary string
     * @return string
     */
    public static function classToLower($str, $sep = self::HZL) {
        $path = str_replace(self::NS, self::URL_SEP, $str);
        return ltrim(strtolower(preg_replace('/([a-z])([A-Z])/', '$1' . $sep . '$2', $path)), $sep);
    }

    /**
     * 将以Toknot开头的类名字转换成路径
     * 
     * @param string $class
     * @return string
     */
    public function toknotClassToPath($class) {
        return substr($class, strlen(self::TOKNOT_NS));
    }

    /**
     * 路径转换成类名字，包括命名空间
     * 
     * @param string $dir
     * @return string
     */
    public static function pathToClass($dir) {
        return str_replace(self::PATH_SEP, self::NS, $dir);
    }

    /**
     * 类名字转换成路径
     * 
     * @param string $ns
     * @return string
     */
    public static function classToPath($ns) {
        return str_replace(self::NS, self::PATH_SEP, $ns);
    }

    public static function thenName($name) {
        return lcfirst(substr($name, 4));
    }

    /**
     * 两个值是否不区分大小写相等
     * 
     * @param scalar $value1
     * @param scalar $value2
     * @return bool
     */
    public static function caseEq($value1, $value2) {
        return strcasecmp($value1, $value2) === 0;
    }

    /**
     * 删除数组指定 key
     * 
     * @param array $array
     * @param scalar $key
     * @return array
     */
    public static function pullOut(&$array, $key) {
        $res = $array[$key];
        unset($array[$key]);
        return $res;
    }

    /**
     * 计算 hash 值
     * 当明确算法与是否使用 hmac 算法时，将回按传入值调用相应的算法函数
     * 当未明确使用算法信息时，会对平台进行算法侦测，选者最安全 hanh 算法
     * 检测顺序：
     * 1、是否存在 hash_hmac 函数，有则使用 sha256 算法，
     * 2、依次检测 sha3-256, sha384, sha224, sha256, 存在则使用该算法
     * 3、保存已使用算法与是否使用 hmac 算法，除非明确指定，二次调用时将使用上次使用的算法
     * 
     * @staticvar string $useAlgo
     * @staticvar bool $hmac
     * @param string $data
     * @param string $key
     * @param bool $raw
     * @param string $useAlgo
     * @param bool $hmac
     * @return string
     */
    public static function hash($data, $key, $raw = false, &$useAlgo = '', &$hmac = false) {
        static $useAlgo, $hmac;
        if ($useAlgo && $hmac) {
            return hash_hmac($useAlgo, $data, $key, $raw);
        } elseif ($useAlgo) {
            return hash($useAlgo, $data . $key, $raw);
        } elseif (function_exists('hash_hmac')) {
            $useAlgo = 'sha256';
            $hmac = true;
            return hash_hmac('sha256', $data, $key, $raw);
        }
        $hmac = false;
        $algoOrder = ['sha3-256', 'sha384', 'sha224', 'sha256'];
        $algoList = hash_algos();
        foreach ($algoOrder as $algo) {
            if (in_array($algo, $algoList)) {
                $useAlgo = $algo;
                return hash($algo, $data . $key, $raw);
            }
        }
        Kernel::runtimeException('your php not support secure hash algo', E_USER_ERROR);
    }

    public function appControllerNs() {
        return \Toknot\App\Controller::class;
    }

    /**
     * 
     * @return string
     */
    public function appViewNs() {
        return \Toknot\App\View::class;
    }

    /**
     * multiple server must has same platfrom software that is php, mysqlnd, webserver ,os release
     * 单机器值：本文件节点ID + PHP版本ID + 系统uname值 + 主配置文件hash值
     * 多机器值：相同的PHP版本ID + 相同系统名称 + 相同系统版本 + 相同机器类型 + 相同的主配置文件hash值
     * 
     * @param bool $single
     * @return string
     */
    public static function serverEntropy($single = true) {
        if (self::$serverEntropy) {
            return self::$serverEntropy;
        }
        if (empty(self::$configHash)) {
            self::$configHash = sha1_file(self::$selfInstance->configFile);
        }
        if ($single) {
            self::$serverEntropy = getmyinode() . PHP_VERSION_ID . php_uname('a') . self::$configHash;
        } else {
            self::$serverEntropy = PHP_VERSION_ID . Request::webserver() . php_uname('s') . php_uname('r') . php_uname('m') . self::$configHash;
        }
        return self::$serverEntropy;
    }

}

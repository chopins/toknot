<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use Toknot\Exception\BaseException;
use Toknot\Boot\Object;
use Toknot\Boot\ParseConfig;

/**
 * Tookit
 *
 * @author chopin
 */
class Tookit extends Object {

    private static $incData = [];
    private static $shutdownFunction = null;
    private static $parseConfObject = null;
    private static $phpfilter = true;
    private static $phpSupportVar = [];

    const EQ_0 = 0;
    const LT_0 = -1;
    const GT_0 = 1;
    const INPUT_GET = 'GET';
    const INPUT_POST = 'POST';
    const INPUT_COOKE = 'COOKE';
    const INPUT_SERVER = 'SERVER';
    const INPUT_ENV = 'ENV';
    const FILTER_DEFAULT = 'FILTER_UNSAFE_RAW';
    const FILTER_UNSAFE_RAW = 'FILTER_UNSAFE_RAW';
    const FILTER_VALIDATE_EMAIL = 'FILTER_VALIDATE_EMAIL';
    const FILTER_VALIDATE_INT = 'FILTER_VALIDATE_INT';
    const FILTER_VALIDATE_FLOAT = 'FILTER_VALIDATE_FLOAT';
    const FILTER_VALIDATE_URL = 'FILTER_VALIDATE_URL';
    const FILTER_VALIDATE_IP = 'FILTER_VALIDATE_IP';

    public static function setParseConfObject($parseClass) {
        if ($parseClass) {
            if (!class_exists($parseClass, false)) {
                throw new \Exception("class $parseClass is unload");
            }
            if (!$parseClass instanceof ParseConfig) {
                throw new \Exception("$parseClass must implement Toknot\Boot\ParseConfig");
            }
            self::$parseConfObject = new $parseClass;
        }
    }

    /**
     * Uppercase the first character of each word in a string
     * 
     * @param string $words
     * @param string $delimiters
     * @return string
     */
    public static function ucwords($words, $delimiters = '.') {
        $delimiters = " \t\r\n\f\v" . $delimiters;
        $v = PHP_VERSION;
        if ((version_compare($v, '5.4.32', '>=') && version_compare($v, '5.5', '<')) || version_compare($v, '5.5.16', '>=')) {
            return ucwords($words, $delimiters);
        } else {
            $del = str_split($delimiters);
            $char = str_split($words);
            $ks = array_keys($char, $del);
            foreach ($ks as $idx) {
                $uchar = $idx + 1;
                if (isset($char[$uchar])) {
                    $char[$uchar] = ucfirst($char[$uchar]);
                }
            }
            return ucfirst(join($char));
        }
    }

    public static function attachShutdownFunction($callable) {
        if (!self::$shutdownFunction instanceof \SplObjectStorage) {
            self::$shutdownFunction = new \SplObjectStorage;
        }
        self::$shutdownFunction->attach($callable);
    }

    public static function releaseShutdownHandler() {
        if (self::$shutdownFunction instanceof \SplObjectStorage) {
            foreach (self::$shutdownFunction as $func) {
                $func();
                self::$shutdownFunction->detach($func);
            }
        }
    }

    public static function splitKey(&$re, $key, $v) {
        $keylist = array_reverse(explode('.', $key));
        $sub = [];
        foreach ($keylist as $i => $k) {
            if ($i == 0) {
                $tmp = [];
                $sub[$k] = is_array($v) ? self::splitValueKey($tmp, $v) : $v;
            } else {
                $tmp = [];
                $tmp[$k] = $sub;
                $sub = $tmp;
            }
        }
        if (array_key_exists($k, $re)) {
            $re[$k] = array_merge_recursive($re[$k], $sub[$k]);
        } else {
            return $re[$k] = $sub[$k];
        }
    }

    public static function pathJoin() {
        $paths = func_get_args();
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);
    }

    public static function nsJoin() {
        $ns = func_get_args();
        return join(PHP_NS, $ns);
    }

    public static function dotNS($class) {
        return str_replace('.', PHP_NS, $class);
    }

    public static function osIsnix() {
        $lower = strtolower(PHP_OS);
        if (strpos($lower, 'win') === 0) {
            return false;
        }
        return true;
    }

    /**
     * generate the path realpath use given root path
     * 
     * @param string $path
     * @param string $relative
     * @return string
     */
    public static function realpath($path, $relative) {
        $chk = self::osIsnix() ?
                (strpos($path, DIRECTORY_SEPARATOR) === 0) : (strpos($path, ':/') === 1);
        $relative = rtrim($relative, DIRECTORY_SEPARATOR);
        if ($chk) {
            return $path;
        } else {
            return $relative . DIRECTORY_SEPARATOR . $path;
        }
    }

    public static function splitValueKey(&$re, $data) {
        foreach ($data as $k => $v) {
            self::splitKey($re, $k, $v);
        }
        return $re;
    }

    /**
     * parse ini file, the function will use dot split item name to array key
     * like below is example:
     * <code>
     * [foo.a]
     * item.sub = value
     * </code>
     * <code>
     * [ foo => [ a => [ item => [ sub=>value] 
     *                 ] 
     *          ] 
     * ]
     * </code>
     * 
     * @param string $file
     * @return array
     */
    public static function parseIni($file) {
        $data = parse_ini_file($file, true);
        $re = [];
        self::splitValueKey($re, $data);
        return $re;
    }

    /**
     * ini config file to convert php array file
     * 
     * @param string $ini
     * @param string $php
     */
    public static function conf2php($ini, $php) {
        $data = self::parseConf($ini);
        $str = '<?php return ' . var_export($data, true) . ';';
        file_put_contents($php, $str, LOCK_EX);
    }

    /**
     * 
     * @param string $lock    lock file
     * @return boolean
     */
    public static function lock($lock) {
        if (file_exists($lock)) {
            return false;
        }
        if (!fopen($lock, 'x')) {
            return false;
        }
        return true;
    }

    public static function type($var) {
        switch ($var) {
            case 'false':
                return false;
            case 'true':
                return true;
            case '~':
                return null;
        }
        if (is_numeric($var)) {
            return (float) $var;
        }
        return $var;
    }

    public static function yamlBlockLiteral(&$i, $s, $cn, $flags) {
        $preIndent = false;
        $block = '';
        $type = substr($flags, 0, 1);
        while (true) {
            if ($cn <= $i) {
                break;
            }
            $l = $s[$i];
            $indent = strspn($l, ' ');
            if ($preIndent !== false && $preIndent != $indent) {
                $i--;
                break;
            }
            $preIndent = $indent;
            $block .= $l . ($type == '|' ? PHP_EOL : '');
            $i++;
        }
        if (stlen($type) > 1) {
            $block .= PHP_EOL;
        }
        return $block;
    }

    public static function eachYaml(&$i, $s, $cn, $preIndent = false, &$anchor = []) {
        $res = [];
        $selfIndent = false;
        while (true) {
            if ($cn <= $i) {
                break;
            }

            $l = trim($s[$i]);
            if (empty($l)) {
                $i++;
                continue;
            }
            if (strpos($l, '#') === 0) {
                $i++;
                continue;
            }

            $indent = strspn($l, ' ');
            if ($selfIndent !== false && $indent < $selfIndent) {
                $i--;
                break;
            }


            if ($preIndent !== false && $preIndent >= $indent) {
                $i--;
                return '';
            }
            if ($selfIndent !== false && $selfIndent < $indent) {
                throw new BaseException("line $i is not aligned");
            }
            $sub = explode(':', $l, 2);
            //数组
            $selfIndent = $indent;
            if (count($sub) == 1) {
                if (strpos($l, '-') !== 0) {
                    throw new BaseException("colon of key not found in line $i");
                }
                $res[] = self::type(trim(ltrim(trim($l), '-')));
            } else {
                $key = trim($sub[0]);
                $var = trim($sub[1]);
                $checkAnchor = (strpos($var, '&') === 0);
                if (empty($var) || $checkAnchor === true) {
                    $i++;
                    $res[$key] = self::eachYaml($i, $s, $cn, $indent, $anchor);
                    if ($checkAnchor) {
                        $anchorKey = trim($var, '&');
                        $anchor[$anchorKey] = &$res[$key];
                    }
                } elseif ($key == '<<' && strpos($var, '*') === 0) {
                    $alias = trim($var, '*');
                    if (!isset($anchor[$alias])) {
                        throw new BaseException("anchor $alias not found");
                    }
                    $res = array_merge($res, $anchor[$alias]);
                } else {
                    if ($var == '|' || $var == '>') {
                        $res[$key] = self::yamlBlockLiteral($i, $s, $cn, $var);
                    } else {
                        $res[$key] = self::type($var);
                    }
                }
            }
            $i++;
        }
        return $res;
    }

    public static function parseSampleYaml($yaml) {
        $content = file_get_contents($yaml);
        $s = explode("\n", $content);
        $cn = count($s);
        $i = 0;
        $anchor = [];
        return self::eachYaml($i, $s, $cn, false, $anchor);
    }

    /**
     * ini config file to convert php array file 
     * and create php symlink to the array file
     * 
     * @param string $ini       ini of config source file
     * @param string $php       symlink of php
     * @param callable $call    call function
     * @param boolean $force    force create cache
     * @return int
     */
    public static function createCache($ini, $php, $call, $force = false) {
        clearstatcache();
        if (!$force && file_exists($php) && filemtime($ini) <= filemtime($php)) {
            return self::LT_0;
        }

        $phplock = "$php.lock";

        if (!self::lock($phplock) && !$force) {
            return self::EQ_0;
        }

        self::attachShutdownFunction(function() use($phplock, $call, $ini, $php) {
            if (file_exists($phplock)) {
                $call($ini, $php);
                unlink($phplock);
            }
        });

        $call($ini, $php);
        unlink($phplock);
        return self::GT_0;
    }

    public static function readCache($ini, $php, $parseFunction) {
        $res = self::createCache($ini, $php, $parseFunction);

        if ($res === self::EQ_0) {
            return self::parseConf($ini);
        }
        $key = md5($ini);
        if (!isset(self::$incData[$key]) || $res === 1) {
            self::$incData[$key] = include_once $php;
        }

        return self::$incData[$key];
    }

    public static function includeCache($ini, $php, $parseFunction) {
        $res = self::createCache($ini, $php, $parseFunction);
        while ($res === 0) {
            $res = self::createCache($ini, $php, $parseFunction);
        }

        return include_once $php;
    }

    public static function parseConf($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext == 'ini') {
            return self::parseIni($file);
        } elseif ($ext == 'yml') {
            return self::parseSampleYaml($file);
        } elseif (self::$parseConfObject !== null && self::$parseConfObject->support($ext)) {
            return self::$parseConfObject->parse($file);
        }
        throw new BaseException("$ext type un-support, current only support ini,yml file");
    }

    /**
     * read a config
     * 
     * @param string $ini
     * @param string $php
     * @return array
     */
    public static function readConf($ini, $php) {
        return self::readCache($ini, $php, function($ini, $target) {
                    self::conf2php($ini, $target);
                });
    }

    /**
     * find needle string in array value
     * 
     * @param array $array
     * @param string $needle
     * @return array
     */
    public static function arrayStrpos(array $array, $needle) {
        $ret = [];
        foreach ($array as $v) {
            if (strpos($v, $needle) !== false) {
                $ret[] = $v;
            }
        }
        return $ret;
    }

    /**
     * if array value in string, return first find the value
     * 
     * @param array $arr
     * @param string $need
     * @param boolean $case
     * @return string|boolean
     */
    public static function arrayPos($arr, $need, $case = false) {
        if (!is_array($arr) && !$arr instanceof \Iterator) {
            throw new BaseException('Argument 1 must be of array or can be iterator');
        }
        $func = $case ? 'strpos' : 'stripos';
        foreach ($arr as $v) {
            if ($func($need, $v) !== false) {
                return $v;
            }
        }
        return false;
    }

    /**
     * 
     * @param array $arr
     * @param array $keys
     * @return mixed
     */
    public static function arrayFind($arr, $keys) {
        $cur = $arr;
        foreach ($keys as $k) {
            if (empty($k)) {
                break;
            }
            if (array_key_exists($k, $cur)) {
                $cur = $cur[$k];
            } else {
                return null;
            }
        }
        return $cur;
    }

    /**
     * check a key of array if empty set default value
     * 
     * @param array &$arr
     * @param string $key
     * @param mix $def
     * @return mix
     */
    public static function coalesce(&$arr, $key, $def = '') {
        if (!is_array($arr) && !$arr instanceof \ArrayAccess) {
            throw new BaseException('Argument 1 must be of array or can be array access');
        }
        $arr[$key] = array_key_exists($key, $arr) ? $arr[$key] : $def;
        return $arr[$key];
    }

    public static function coal($check, $value) {
        return $check ? $check : $value;
    }

    /**
     * check a key of array if empty set is [], or use delimit split to array
     * 
     * @param array $param
     * @param string $key
     * @param string $del
     * @return array
     */
    public static function splitStr(array &$param, $key, $del = ',', $setDef = []) {
        $param[$key] = array_key_exists($key, $param) ? explode($del, $param[$key]) : $setDef;
        return $param[$key];
    }

    /**
     * delete array values, specifying keys
     * 
     * @param array $arr
     * @param string $key1 ...
     */
    public static function arrayUnset(array &$arr, $key1 /* ,...$keys */) {
        $argv = func_get_args();
        unset($argv[0]);
        $arr = array_diff_key($arr, array_flip($argv));
    }

    /**
     * delete array values, specifying some keys,return new array or object
     * 
     * @param array $arr
     * @param string $key ...
     * @return array
     */
    public static function arrayRemove($arr, $key) {
        if (is_object($arr)) {
            $arr = clone $arr;
        }
        $argv = func_get_args();
        unset($argv[0]);
        foreach ($argv as $k) {
            unset($arr[$k]);
        }
        return $arr;
    }

    /**
     * join array values to string, specifying keys
     * 
     * @param array $arr
     * @param string $glue
     * @param string $key1,$key2....
     * @return string
     */
    public static function join(array $arr, $glue /* ,...$params */) {
        $argv = func_get_args();
        self::arrayUnset($argv, 0, 1);
        $intersect = array_intersect_key($arr, array_flip($argv));
        return join($glue, $intersect);
    }

    /**
     * shift more elements off the beginning of array
     * 
     * @param array $arr
     * @param int $c
     */
    public static function shift(array &$arr, $c) {
        for ($i = 0; $i < $c; $i++) {
            array_shift($arr);
        }
    }

    /**
     * delete value of specify key and return the value
     * 
     * @param array $arr
     * @param string $key
     * @return mix
     */
    public static function arrayDelete(array &$arr, $key) {
        $res = false;
        if (array_key_exists($key, $arr)) {
            $res = $arr[$key];
            unset($arr[$key]);
        }
        return $res;
    }

    public static function getRealPath($path, $cwd = '') {
        $isRoot = false;
        if (strtolower(substr(PHP_OS, 0, 3)) == 'WIN') {
            if (preg_match('/^[A-Z]:\//i', $path)) {
                $isRoot = true;
            }
        } elseif (strpos($path, DIRECTORY_SEPARATOR) === 0) {
            $isRoot = true;
        }
        if ($isRoot) {
            return $path;
        }
        if (!$cwd) {
            $cwd = getcwd();
        }
        return "{$cwd}/{$path}";
    }

    /**
     * Get string length
     * 
     * @param string $str
     * @return int
     */
    public static function strlen($str, $charset = null) {
        $func = self::getStrFunc(__FUNCTION__);
        if ($charset && $func != __FUNCTION__) {
            return $func($str, $charset);
        }
        return $func($str);
    }

    /**
     * Get part of string
     * 
     * @param string $str
     * @param int $start
     * @param int $length
     * @param string $encoding
     * @return string
     */
    public static function substr($str, $start, $length = null, $encoding = null) {
        $argv = func_get_args();
        if ($encoding === null) {
            unset($argv[3]);
        }
        $func = self::getStrFunc(__FUNCTION__);
        return self::callFunc($func, $argv);
    }

    /**
     * Find position of first occurrence of string in a string
     * 
     * @param string $str
     * @param string $needle
     * @param int $offset
     * @param string $encoding
     * @return int
     */
    public static function strpos($str, $needle, $offset = 0, $encoding = null) {
        $argv = func_get_args();
        if ($encoding === null) {
            unset($argv[3]);
        }
        $func = self::getStrFunc(__FUNCTION__);
        return self::callFunc($func, $argv);
    }

    public function strrpos($str, $needle, $offset = 0, $encoding = null) {
        $argv = func_get_args();
        if ($encoding === null) {
            unset($argv[3]);
        }
        $func = self::getStrFunc(__FUNCTION__);
        if ($func == 'iconv_strpos') {
            $argv = [$str, $needle, $encoding];
        }

        return self::callFunc($func, $argv);
    }

    private static $strFuncPrefix = null;

    public static function getStrFunc($func) {
        if (self::$strFuncPrefix === null) {
            self::$strFuncPrefix = (extension_loaded('mbstring') ? 'mb_' : (extension_loaded('iconv') ? 'iconv_' : ''));
        }

        return self::$strFuncPrefix . $func;
    }

    /**
     * remove a dir, if set recursion will remove sub dir and file
     * 
     * @param string $folder
     * @param boolean $recursion
     * @return boolean
     */
    public static function rmdir($folder, $recursion = false) {
        if ($recursion === false) {
            return rmdir($folder);
        }

        self::dirWalk($folder, 'unlink', 'rmdir');
    }

    public static function getStreamWrappersData($uri, $opt, &$fp) {
        $context = stream_context_create($opt);
        $fp = fopen($uri, 'r', false, $context);
        $stat = stream_get_meta_data($fp);
        if ($stat['wrapper_data']) {
            return $stat['wrapper_data'];
        }
    }

    /**
     * 
     * @param boolean $returnSec
     * @return int
     */
    public static function getTimezoneOffset($returnSec = false) {
        if ($returnSec) {
            return date('Z');
        }
        $number = date('P');
        list($hour, $min) = explode(':', $number);
        $sign = substr($number, 0, 1);
        $hpad = substr($hour, 1, 1) * 10;
        $mpad = substr($min, 0, 1) * 10;

        $h = $hpad + substr($number, 2);
        $m = $mpad + substr($min, 1) / 60;
        return $sign . ($h + $m);
    }

    /**
     * set timezone, support time offset hours
     * 
     * @param string $zone
     */
    public static function setTimeZone($zone) {
        if (is_numeric($zone)) {
            $zone = (int) $zone * -1;
            $zone = 'ETC/GMT' . ($zone > 0 ? "+$zone" : "$zone");
        }
        date_default_timezone_set($zone);
    }

    public static function disablePHPFilter() {
        if (!self::$phpfilter) {
            return;
        }
        self::$phpfilter = false;
        self::$phpSupportVar['GET'] = $_GET;
        self::$phpSupportVar['POST'] = $_POST;
        self::$phpSupportVar['COOKIE'] = $_COOKIE;
        self::$phpSupportVar['ENV'] = $_ENV;
    }

    public static function isExternal($type, $key) {
        if (self::$phpfilter) {
            $type = constant("INPUT_$type");
            return filter_has_var($type, $key);
        }
        if ($type == self::INPUT_SERVER) {
            return getenv($key, true) ? true : false;
        }
        return isset(self::$phpSupportVar[$type][$key]);
    }

    public static function isEmail($string) {
        if (strpos($value, '@') < 1) {
            return false;
        }
        return preg_match('/^[a-z0-9]+([\._]?[a-z0-9]+)*@[a-z0-9]+([\.-]?[a-z]+)*$/i', $string);
    }

    public static function isIp($value) {
        $ip4 = explode('.', $value);
        if (count($ip4) == 4) {
            foreach ($ip4 as $n) {
                if (!is_numeric($n) || $n < 0 || $n > 255) {
                    return false;
                }
            }
            return true;
        }
        $ip6 = explode(':', $value);
        $bn = count($ip6);
        if ($bn < 7 && $bn > 2) {
            $zero = $iszero = $upzero = 0;
            foreach ($ip6 as $n) {
                if (empty($n)) {
                    $zero ++;
                    $iszero = 1;
                } else {
                    $iszero = 0;
                }
                if ($zero > 1 && !$upzero) {
                    return false;
                }
                $upzero = $iszero;
            }
            return preg_match('/^[a-f0-9\:]+$/i', $value);
        }
        return false;
    }

    public static function isUrl($value) {
        $urls = parse_url($value);
        if (!$urls) {
            return false;
        }
        if (!is_array($urls)) {
            return false;
        }
        if (empty($urls['scheme']) || $urls['scheme'] != 'http' || $urls['scheme'] != 'https') {
            return false;
        }
        if (empty($urls['host'])) {
            return false;
        }
        return true;
    }

    public static function isFloat($value) {
        if (is_float($value)) {
            return true;
        }
        if (is_numeric($value) && strpos($value, '.') !== false) {
            return true;
        }
        if (is_numeric($value) && $value > PHP_INT_MAX) {
            return true;
        }
        return false;
    }

    public static function isInt($value) {
        if (is_int($value)) {
            return true;
        }
        if (is_numeric($value) && strpos($value, '.') === false && $value <= PHP_INT_MAX) {
            return true;
        }
        return false;
    }

    public static function filter($type, $key, $filter = self::FILTER_DEFAULT) {
        if (self::$phpfilter) {
            $type = constant("INPUT_$type");
            return filter_input($type, $key, constant($filter));
        }
        if (!isset(self::$phpSupportVar[$type][$key])) {
            return null;
        }

        $value = $type == self::INPUT_SERVER ? getenv($key) : self::$phpSupportVar[$type][$key];
        switch ($filter) {
            case self::FILTER_VALIDATE_EMAIL:
                if (self::isEmail($value)) {
                    return $value;
                }
                return false;
            case self::FILTER_VALIDATE_FLOAT:
                if (self::isFloat($value)) {
                    return (float) $value;
                }
                return false;
            case self::FILTER_VALIDATE_INT:
                if (self::isInt($value)) {
                    return (int) $value;
                }
                return false;
            case self::FILTER_VALIDATE_IP:
                if (self::isIp($value)) {
                    return $value;
                }
                return false;
            case self::FILTER_VALIDATE_URL:
                if (self::isUrl($value)) {
                    return $value;
                }
                return false;
            case self::FILTER_UNSAFE_RAW:
            default:
                return $value;
        }
    }

    public static function env($key) {
        return PHP_MIN_VERSION > 6 ? getenv($key, true) : getenv($key);
    }

    public static function dirWalk($dir, $callable, $dirCallable = null) {
        $d = dir($dir);
        while (false !== ($enter = $d->read())) {
            if ($enter == '.' || $enter == '..') {
                continue;
            }
            $path = "$dir/$enter";
            if (is_dir($path)) {
                self::dirWalk($path, $callable, $dirCallable);
            } else {
                $callable($path);
            }
        }
        $dirCallable && $dirCallable($dir);
    }

    public static function hookEmpty($var) {
        return empty($var);
    }

}

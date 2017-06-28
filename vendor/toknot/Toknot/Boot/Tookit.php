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

use Toknot\Exception\BaseException;
use Toknot\Share\Generator;

/**
 * Tookit
 *
 * @author chopin
 */
class Tookit {

    use ObjectHelper;

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

    /**
     * join string to path
     * 
     * @param string $path1
     * @return string
     */
    public static function pathJoin() {
        $paths = func_get_args();
        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);
    }

    /**
     * join string to namspace
     * 
     * @return string
     */
    public static function nsJoin() {
        $ns = func_get_args();
        return join(PHP_NS, $ns);
    }

    /**
     * covert class of dot to namespace separator
     * 
     * @param string $class
     * @return string
     */
    public static function dotNS($class) {
        return str_replace('.', PHP_NS, $class);
    }

    public static function path2NS($path) {
        return str_replace('/', PHP_NS, $path);
    }

    /**
     * check os whehter not windows
     * 
     * @return boolean
     */
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
     * check a key of array if empty set default value, array value coalsece opreate
     * 
     * @param array &$arr
     * @param string $key
     * @param mix $def
     * @return mix
     */
    public static function coalesce(&$arr, $key, $def = '') {
        if (!is_array($arr) && !$arr instanceof \Iterator) {
            throw new BaseException('Argument 1 must be of array or can be array access');
        }
        if($arr instanceof \Iterator) {
            $arr = iterator_to_array($arr);
        }
        $arr[$key] = array_key_exists($key, $arr) ? $arr[$key] : $def;
        return $arr[$key];
    }

    /**
     * if specify value is false return give value, coalsece operate
     * 
     * @param mix $check
     * @param mix $value
     * @return mix
     */
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
    public static function arrayJoin(array $arr, $glue /* ,...$params */) {
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
    public static function arrayShift(array &$arr, $c) {
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

    /**
     * get real path without check path exists
     * 
     * @param string $path
     * @param string $cwd
     * @return string
     */
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

    public static function strrpos($str, $needle, $offset = 0, $encoding = null) {
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

    public static function getStrFunc($func) {
        static $strFuncPrefix = null;
        if ($strFuncPrefix === null) {
            $strFuncPrefix = (extension_loaded('mbstring') ? 'mb_' : (extension_loaded('iconv') ? 'iconv_' : ''));
        }

        return $strFuncPrefix . $func;
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

    /**
     * get wrapper data
     * 
     * @param string $uri
     * @param array $opt
     * @param resources $fp
     * @return array
     */
    public static function getStreamWrappersData($uri, $opt, &$fp) {
        $context = stream_context_create($opt);
        $fp = fopen($uri, 'r', false, $context);
        $stat = stream_get_meta_data($fp);
        if ($stat['wrapper_data']) {
            return $stat['wrapper_data'];
        }
    }

    /**
     * get current timezone offset time
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

    public static function isEmail($string) {
        if (strpos($value, '@') < 1) {
            return false;
        }
        return preg_match('/^[a-z0-9]+([\._]?[a-z0-9]+)*@[a-z0-9]+([\.-]?[a-z]+)*$/i', $string);
    }

    /**
     * check ipv4 or ipv6 is it effective
     * 
     * @param string $value
     * @return boolean
     */
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

    /**
     * check url is it effcetive
     * 
     * @param string $value
     * @return boolean
     */
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

    /**
     * check number or string whether or not float
     * 
     * @param mix $value
     * @return boolean
     */
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

    /**
     * check number or string whether or not integer
     * 
     * @param mix $value
     * @return boolean
     */
    public static function isInt($value) {
        if (is_int($value)) {
            return true;
        }
        if (is_numeric($value) && strpos($value, '.') === false && $value <= PHP_INT_MAX) {
            return true;
        }
        return false;
    }

    public static function filterXSS($value) {
        $str = rawurldecode($value);
        $str = str_replace(['<', '>', '.', '(', ')'], ['&lt;', '&gt;', '&#46;', '&#40;', '&#41;'], $str);
        $str = preg_replace('/=([\s\'"]*)javascript(\s*:+)/im', '=$1javascript&#58;', $str);
        $str = preg_replace('/(\s)on([\w]+)/im', '&nbsp;on$2', $str);
        $str = preg_replace('/=([\s\'"]*)data(\s*:+)/im', '=$1data&#58;', $str);
        return $str;
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

    public static function xrange($start, $end, $step = 1) {
        if (PHP_MIN_VERSION < 5 || !is_numeric($start) || !is_numeric($end)) {
            return range($start, $end, $step);
        }
        if (($end - $start) * $step < 0) {
            throw new BaseException('step error');
        }
        if ($step == 0) {
            throw new BaseException('step error');
        }
        return Generator::iteration($start, $end, $step);
    }

    public static function arrayColumn($array, $key, $indexKey = null) {
        if (function_exists('array_column')) {
            return array_column($array, $key, $indexKey);
        }
        if ($key === null && $indexKey === null) {
            return $array;
        }
        $res = array();
        foreach ($array as $row) {
            if ($key === null) {
                $res[$row[$indexKey]] = $row;
                continue;
            }
            if ($indexKey) {
                $res[$row[$indexKey]] = $row[$key];
            } else {
                $res[] = $row[$key];
            }
        }
        return $res;
    }

    public static function iter($number, $callable) {
        $idx = 0;
        while ($number > $idx) {
            $idx++;
            self::callFunc($callable, [$idx]);
        }
    }

    /**
     * return current unix timestamp with millisecond
     * 
     * @return float
     */
    public static function millisecond() {
        return floor(microtime(true) * 1000);
    }

    /**
     * sleep millisecond
     * 
     * @param int $int
     */
    public static function msleep($int) {
        usleep($int * 1000);
    }

    /**
     * covert data size form bytes to other unit
     * 
     * <code>
     * $number = 10440;
     * Tookit::formatDataSize($number) //10.1953125 KB
     * Tookit::formatDataSize($number, 'd') //10.44 KB
     * Tookit::formatDataSize($number, 'i') //10.1953125 KiB
     * Tookit::formatDataSize($number, 'd', 1) //10.4 KB
     * Tookit::formatDataSize($number, 's', 1) //10.2 KB
     * Tookit::formatDataSize($number, 3) //10.195 KB
     * </code>
     * 
     * @param int $number           bytes
     * @param boolean $format       format type, s,i is carry 1024, d is carry 1000
     * @param boolean $precision    rounds the float number size
     * @return string
     */
    public static function formatDataSize($number, $format = 's', $precision = null) {
        $unit = ['K', 'M', 'G', 'T', 'P'];
        $carry = $format == 'd' ? 1000 : 1024;
        $iecPrefix = $format == 'i' ? 'i' : '';
        if (is_numeric($format) && $precision === null) {
            $precision = $format;
        }
        foreach ($unit as $u) {
            $k = $number / $carry;
            if ($k < $carry) {
                break;
            }
        }
        if ($precision !== null) {
            $k = round($k, $precision);
        }
        return "{$k} {$u}{$iecPrefix}B";
    }

    public static function underline2Camel($str) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    public static function camel2Underline($token) {
        return strtolower(preg_replace('/([A-Z])/', "_$1", lcfirst($token)));
    }

}

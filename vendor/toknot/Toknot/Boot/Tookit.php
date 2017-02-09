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

/**
 * Tookit
 *
 * @author chopin
 */
class Tookit {

    private static $incData = [];
    private static $shutdownFunction = null;

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
        if (isset($re[$k])) {
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
    public static function ini2php($ini, $php) {
        $data = self::parseIni($ini);
        $str = '<?php return ' . var_export($data, true) . ';';
        file_put_contents($php, $str);
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
        if (!@fopen($lock, 'x')) {
            return false;
        }
        return true;
    }

    /**
     * ini config file to convert php array file 
     * and create php symlink to the array file
     * 
     * @param string $ini   ini of config source file
     * @param string $php       symlink of php
     * @param callable $call    call function
     * @return int
     */
    public static function createCache($ini, $php, $call) {
        clearstatcache();
        if (file_exists($php) && filemtime($ini) <= filemtime($php)) {
            return -1;
        }

        $phplock = "$php.lock";

        if (!self::lock($phplock)) {
            return 0;
        }

        self::attachShutdownFunction(function() use($phplock, $call, $ini, $php) {
            if (file_exists($phplock)) {
                $call($ini, $php);
                unlink($phplock);
            }
        });

        $call($ini, $php);
        unlink($phplock);
        return 1;
    }

    public static function readCache($ini, $php, $parseFunction) {
        $res = self::createCache($ini, $php, $parseFunction);

        if ($res === 0) {
            return self::parseIni($ini);
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

    /**
     * read a ini config
     * 
     * @param string $ini
     * @param string $php
     * @return array
     */
    public static function readini($ini, $php) {
        return self::readCache($ini, $php, function($ini, $target) {
                    self::ini2php($ini, $target);
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
        if(!is_array($arr) && !$arr instanceof \Iterator) {
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
     * check a key of array if empty set default value
     * 
     * @param array &$arr
     * @param string $key
     * @param mix $def
     * @return mix
     */
    public static function coalesce(&$arr, $key, $def = '') {
        if(!is_array($arr) && !$arr instanceof \ArrayAccess) {
            throw new BaseException('Argument 1 must be of array or can be array access');
        }
        $arr[$key] = empty($arr[$key]) ? $def : $arr[$key];
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
        $param[$key] = empty($param[$key]) ? $setDef : explode($del, $param[$key]);
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
        if (isset($arr[$key])) {
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

}

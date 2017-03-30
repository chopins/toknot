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
use Toknot\Boot\ParseConfig;
use Toknot\Exception\NoFileOrDirException;

class Configuration extends Object {

    private static $parseConfObject = null;
    private static $incData = [];

    public function __construct($cfg) {
        $this->iteratorArray = $cfg;
    }

    /**
     * not found return null else return the key value
     * 
     * <code>
     * $cfg->find('app.app_ns')
     * </code>
     * 
     * @param string $key
     * @return mixed
     */
    public function find($key) {
        $ks = explode('.', $key);
        $cur = $this->iteratorArray;
        foreach ($ks as $k) {
            if (array_key_exists($k, $cur)) {
                $cur = $cur[$k];
            } else {
                return null;
            }
        }
        return is_array($cur) ? new static($cur) : $cur;
    }

    public function toArray() {
        return $this->iteratorArray;
    }

    public function __get($key) {
        $v = $this->iteratorArray[$key];

        if (is_array($v)) {
            return new static($v);
        } else {
            return $v;
        }
    }

    public function __set($name, $value) {
        $this->iteratorArray[$name] = $value;
    }

    public function __isset($name) {
        return array_key_exists($name, $this->iteratorArray);
    }

    public function __unset($name) {
        unset($this->iteratorArray[$name]);
    }

    public function offsetGet($offset) {
        $v = parent::offsetGet($offset);
        if (is_array($v)) {
            return new static($v);
        } else {
            return $v;
        }
    }

    public function exceptionMkdir($e, $isfile = true) {
        $f = $e->getExceptionFile();
        if ($isfile) {
            mkdir(dirname($f), 0755, true);
        } else {
            mkdir($f, 0755, true);
        }
    }

    public static function loadConfig($ini) {
        $cfg = self::readCache($ini);
        return new static($cfg);
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
    public static function createCache($ini, $force = false) {
        clearstatcache();
        $filename = pathinfo($ini, PATHINFO_FILENAME);
        $php = APPDIR . "/runtime/config/$filename.php";
        if (!$force && file_exists($php) && filemtime($ini) <= filemtime($php)) {
            return $php;
        }

        $data = self::parseConf($ini);
        $str = '<?php return ' . var_export($data, true) . ';';
        try {
            file_put_contents($php, $str, LOCK_EX);
        } catch (NoFileOrDirException $e) {
            $this->exceptionMkdir($e);
            file_put_contents($php, $str, LOCK_EX);
        }
        return $php;
    }

    public static function readCache($ini) {
        $key = md5($ini);
        if (isset(self::$incData[$key])) {
            return self::$incData[$key];
        }

        $php = self::createCache($ini);

        if (!file_exists($php)) {
            self::$incData[$key] = self::parseConf($ini);
        }
        if (!isset(self::$incData[$key])) {
            self::$incData[$key] = include_once $php;
        }

        return self::$incData[$key];
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

    protected static function splitKey(&$re, $key, $v) {
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

    protected static function splitValueKey(&$re, $data) {
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

    protected static function yamlType($var) {
        switch ($var) {
            case 'false':
                return false;
            case 'true':
                return true;
            case '~':
                return null;
            case 'yes':
                return true;
            case 'no':
                return false;
        }
        if (is_numeric($var)) {
            return (float) $var;
        }
        return $var;
    }

    protected static function yamlBlockLiteral(&$i, $s, $cn, $flags) {
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

    protected static function eachYaml(&$i, $s, $cn, $preIndent = false, &$anchor = []) {
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
                $res[] = self::yamlType(trim(ltrim(trim($l), '-')));
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
                        $res[$key] = self::yamlType($var);
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

}

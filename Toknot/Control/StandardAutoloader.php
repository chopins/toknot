<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;
use Toknot\Exception\BadPropertyGetException;
use Toknot\Exception\BadClassCallException;

class StandardAutoloader {

    const NS_SEPARATOR = '\\';

    private $directory = array();
    
    private $importList = array();

    public function __construct($path = '') {
        if ($path == '') {
            $path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        }
        $this->directory[] = $path;
    }

    /**
     * add autoload scan directory
     * 
     * @param string $path
     * @access public
     */
    public function addPath($path) {
        $this->directory[] = $path;
    }

    /**
     * trans form class name to file name that relative a specified directory
     * 
     * @param string $class
     * @param string $dir
     * @return string
     * @static
     * @access public
     */
    public static function transformClassNameToFilename($class, $dir) {
//        $topNamespace = strtok($class, self::NS_SEPARATOR);
//        $lastPath = basename($dir);
//        if ($topNamespace != $lastPath)
//            return false;
        $dir = dirname($dir);
        $nsPath = strtr($class, self::NS_SEPARATOR, DIRECTORY_SEPARATOR);
        $nsPath = ltrim($nsPath, '/');
        return $dir . DIRECTORY_SEPARATOR . $nsPath . '.php';
    }

    public static function transformNamespaceToPath($class, $dir) {
        return rtrim(self::transformClassNameToFilename($class, $dir), '.php');
    }

    /**
     * load a class, the method is PHP autoload handler function
     * 
     * @param string $class
     * @return boolean
     */
    public function autoload($class) {
        foreach ($this->directory as $dir) {
            $filename = self::transformClassNameToFilename($class, $dir);
            if (file_exists($filename)) {
                return require_once $filename;
            }
        }
        return false;
    }

    /**
     * manually import one class of toknot instead autoload
     * 
     * @param string $class
     * @return boolean
     * @access public
     * @static
     */
    public static function importToknotClass($class) {
        $toknotRoot = dirname(__DIR__);
        $path = $toknotRoot . DIRECTORY_SEPARATOR . strtr($class, self::NS_SEPARATOR, DIRECTORY_SEPARATOR);
        return require_once $path . '.php';
    }

    public static function import($className, $aliases = null) {
        $name = substr(strrchr($className, self::NS_SEPARATOR), 1);
        if ($name == '*') {
            $namespace = rtrim($className, '\*');
            
            foreach ($this->directory as $dir) {
                $path = self::transformNamespaceToPath($namespace, $dir);
                if (is_dir($path)) {
                    $fileList = glob("$path/*.php");
                    if ($aliases === null) {
                        foreach ($fileList as $file) {
                            include_once $file;
                            $this->importList[$file] = $namespace . $file;
                        }
                    } else {
                        $this->importList[$aliases] = new \stdClass;
                        foreach ($fileList as $file) {
                            include_once $file;
                            $this->importList[$aliases]->$file = $namespace . $file;
                        }
                    }
                    break;
                }
            }
        } else {
            $aliases || ($aliases = $name);
            $this->importList[$aliases] = $className;
        }
    }
    public function getImprotList($key = null) {
        if($key && isset($this->importList[$key])) {
            return $this->importList[$key];
        } elseif($key) {
            throw new BadClassCallException($key);
        }
        return $this->importList;
    }
    public function __get($name) {
        if($name == 'importList') {
            return $this->importList;
        }
        throw new BadPropertyGetException(__CLASS__,$name);
    }
    /**
     * import under namespace all class of toknot
     * 
     * @param string $module
     * @param string $first
     * @access public
     * @static
     */
    public static function importToknotModule($module, $first = null) {
        $toknotRoot = dirname(__DIR__);
        $path = $toknotRoot . '/' . $module;
        if ($first) {
            include_once "{$path}/{$first}.php";
        }
        $fileList = glob("$path/*.php");
        foreach ($fileList as $file) {
            if ($file == "{$path}/{$first}.php") {
                continue;
            }
            include_once $file;
        }
    }

    /**
     * register a autoload handler function
     */
    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }

}

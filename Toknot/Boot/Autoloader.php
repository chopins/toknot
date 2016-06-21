<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use \Toknot\Exception\BadClassCallException;

class Autoloader {

    const NS_SEPARATOR = '\\';

    public static $fileSuffix = '.php';
    private static $directory = array();
    private static $toknotRoot = null;

    public function __construct($path = '') {
        self::$toknotRoot = dirname(__DIR__);
        if ($path == '') {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        }
        self::$directory[] = $path;
    }
   
    private static function hasInclude($path) {
        $files = get_included_files();
        return in_array($path, $files);
    }
    /**
     * add autoload scan directory
     *
     * @param string $path
     * @access public
     * @static
     */
    private static function addPath($path) {
        self::$directory[] = $path;
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
    private static function transformClassNameToFilename($class, $dir) {
        $dir = dirname($dir);
        $nsPath = strtr($class, self::NS_SEPARATOR, DIRECTORY_SEPARATOR);
        $nsPath = ltrim($nsPath, DIRECTORY_SEPARATOR);
        return $dir . DIRECTORY_SEPARATOR . $nsPath . self::$fileSuffix;
    }

    private static function transformNamespaceToPath($class, $dir) {
        return rtrim(self::transformClassNameToFilename($class, $dir), self::$fileSuffix);
    }

    /**
     * load a class, the method is PHP autoload handler function
     *
     * @param string $class
     * @return null
     * @throws \Toknot\Exception\BadClassCallException
     */
    public function autoload($class) {
        foreach (self::$directory as $dir) {
            $filename = self::transformClassNameToFilename($class, $dir);
            if (file_exists($filename)) {
                return require $filename;
            }
        }
        throw new BadClassCallException($class);
    }
    private static function import($class) {
        try {
            self::importToknotClass($class);
        } catch(\Error $e) {
            try {
                self::importToknotModule($class);
            } catch(\Error $e) {
                throw new BadClassCallException($class);
            }
        }
    }

    /**
     * manually import one class of toknot instead autoload
     *
     * @param string $class
     * @access public
     * @static
     */
    private static function importToknotClass($class) {
        $toknotRoot = dirname(__DIR__);
        $path = $toknotRoot . DIRECTORY_SEPARATOR . strtr($class, self::NS_SEPARATOR, DIRECTORY_SEPARATOR);
        if(self::hasInclude($path)) {
            return;
        }
        require_once $path . self::$fileSuffix;
    }


    /**
     * import under namespace all class of toknot
     *
     * @param string $module
     * @param string $first
     * @access public
     * @static
     */
    private static function importToknotModule($module, $first = null) {
        $module = strtr($module,  self::NS_SEPARATOR, DIRECTORY_SEPARATOR);
        $toknotRoot = dirname(__DIR__);
        $path = $toknotRoot . DIRECTORY_SEPARATOR . $module;
        if ($first) {
            $fistFile = $path . DIRECTORY_SEPARATOR . $first . self::$fileSuffix;
            if(!$this->hasInclude($fistFile)) {
                include_once $fistFile;
            }
        }
        $fileList = glob($path . DIRECTORY_SEPARATOR . '*' . self::$fileSuffix);
        foreach ($fileList as $file) {
            if ($fist && $file == $fistFile) {
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

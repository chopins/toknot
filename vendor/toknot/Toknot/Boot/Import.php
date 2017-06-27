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

class Import {

    public static $fileSuffix = '.php';
    private static $directory = array();
    private static $toknotRoot = null;
    private static $includeList = [];

    public function __construct($path = '') {
        defined('PHP_NS') || define('PHP_NS', '\\');
        self::$toknotRoot = dirname(__DIR__);
        if ($path == '') {
            $path = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        }
        self::$directory[] = $path;
    }

    public static function hasInclude($path) {
        if (self::$includeList && in_array($path, self::$includeList)) {
            return true;
        }
        self::$includeList = get_included_files();
        return in_array($path, self::$includeList);
    }

    /**
     * add autoload scan directory, the path must contain top namespace of dir
     * like: add path: your_path/appPath/TopNs
     *
     * @param string $path
     * @access public
     * @static
     */
    public static function addPath($path) {
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
    public static function transformClassNameToFilename($class, $dir) {
        $dir = dirname($dir);
        $nsPath = strtr($class, PHP_NS, DIRECTORY_SEPARATOR);
        $nsPath = ltrim($nsPath, DIRECTORY_SEPARATOR);
        return $dir . DIRECTORY_SEPARATOR . $nsPath . self::$fileSuffix;
    }

    public static function transformNamespaceToPath($class, $dir) {
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
                self::$includeList[] = $filename;
                return require $filename;
            }
        }
    }

    /**
     * register a autoload handler function
     */
    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }

}

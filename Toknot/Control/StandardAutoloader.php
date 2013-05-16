<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

class StandardAutoloader {

    const NS_SEPARATOR = '\\';

    private $directory = array();

    public function __construct($path = '') {
        if ($path == '') {
            $path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        }
        $this->directory[] = $path;
    }

    public function addPath($path) {
        $this->directory[] = $path;
    }

    public static function transformClassNameToFilename($class, $dir) {
        $topNamespace = strtok($class, self::NS_SEPARATOR);
        $lastPath = basename($dir);
        if ($topNamespace != $lastPath)
            return false;
        $dir = dirname($dir);
        $nsPath = str_replace(self::NS_SEPARATOR, DIRECTORY_SEPARATOR, $class);
        return $dir . DIRECTORY_SEPARATOR . $nsPath . '.php';
    }

    public function autoload($class) {
        foreach ($this->directory as $dir) {
            $filename = self::transformClassNameToFilename($class, $dir);
            if (!$filename)
                continue;
            $resolvedName = stream_resolve_include_path($filename);
            if ($resolvedName !== false) {
                return require_once $resolvedName;
            }
        }
        return false;
    }

    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }

}

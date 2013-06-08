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
//        $topNamespace = strtok($class, self::NS_SEPARATOR);
//        $lastPath = basename($dir);
//        if ($topNamespace != $lastPath)
//            return false;
        $dir = dirname($dir);
        $nsPath = str_replace(self::NS_SEPARATOR, DIRECTORY_SEPARATOR, $class);
        return $dir . DIRECTORY_SEPARATOR . $nsPath . '.php';
    }

    public function autoload($class) {
        foreach ($this->directory as $dir) {
            $filename = self::transformClassNameToFilename($class, $dir);
            if (!$filename)
                continue;
            //$resolvedName = stream_resolve_include_path($filename);
            if (file_exists($filename)) {
                return require_once $filename;
            }
        }
        return false;
    }
    public static function importToknotClass($class) {
        $toknotRoot = dirname(__DIR__);
        $path = $toknotRoot . '/'.str_replace(self::NS_SEPARATOR, DIRECTORY_SEPARATOR, $class);
        return require_once $path.'.php';
    }
    public static function importToknotModule($module) {
        $toknotRoot = dirname(__DIR__);
        $path = $toknotRoot . '/'.$module;
        $dir = dir($path);
        while(false !== ($file=$dir->read())) {
            if($file =='.' ||$file =='..') {
                if(is_file($path.'/'.$file)) {
                    include  $path.'/'.$file;
                }
            }
        }
    }

    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }

}

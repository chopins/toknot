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
    public function __construct($path ='') {
        if($path == '') {
            $path = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR;
        }
        $this->directory[] = $path;
    }
    public function addPath($path) {
        $this->directory[] = $path;
    }

    private function transformClassNameToFilename($class, $dir) {
        $directoryLast = strlen($dir) - 1;
        $nsPath = str_replace(self::NS_SEPARATOR, DIRECTORY_SEPARATOR, $class);
        if(($dir[$directoryLast] == DIRECTORY_SEPARATOR && 
                $nsPath[0] != DIRECTORY_SEPARATOR) || 
                ($dir[$directoryLast] != DIRECTORY_SEPARATOR &&
                $nsPath[0] == DIRECTORY_SEPARATOR)) {
            return $dir. $nsPath . '.php';
        }
        if($dir[$directoryLast] != DIRECTORY_SEPARATOR &&
                $nsPath[0] != DIRECTORY_SEPARATOR) {
            return $dir . DIRECTORY_SEPARATOR . $nsPath . '.php';
        }
    }

    public function autoload($class) {
        foreach($this->directory as $dir) {
            $filename = $this->transformClassNameToFilename($class, $dir);
            $resolvedName = stream_resolve_include_path($filename);
            if($resolvedName !== false) {
                require_once $resolvedName;
            }
        }
        return false;
    }
    
    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }
}

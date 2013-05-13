<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Config;

use Toknot\Di\Object;
use Toknot\Config\ConfigObject;

final class ConfigLoader extends Object {
    private static $_CFG = null;
    protected function __construct($file) {
        $this->loadCfg($file);
        $this->_CFG = new ConfigObject();
    }
    public static function singleton($file) {
        return parent::__singleton($file);
    }
    /**
     * detach 
     * 
     * @param array $array 
     * @static
     * @access private
     * @return array
     */
    private function detach($array) {
        return new ConfigObject($array);
    }

    /**
     * load configuration file
     * 
     * @access public
     * @return void
     */
    private function loadCfg($file) {
        $defaultConfig = parse_ini_file(__DIR__. '/default.ini');
        if(file_exists($file)) {
            $userConfig =  parse_ini_file($file, true);
            $defaultConfig = array_replace_recursive($defaultConfig,$userConfig);
        }
        self::$_CFG = $this->detach($defaultConfig);
        var_dump(self::$_CFG);
    }
    
}


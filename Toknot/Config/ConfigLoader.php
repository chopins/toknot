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

final class ConfigLoader extends Object {
    private static $_CFG = null;
    private static $_CFGOBJ = null;
    protected function __construct($file) {
        $this->loadCfg($file);
    }
    public static function singleton($file) {
        return parent::__singleton($file);
    }

    /**
     * parse_ini 
     * 
     * @param string $iniFile 
     * @static
     * @access public
     * @return array
     */
    public static function parseIni($iniFile) {
        $oIni = parse_ini_file($iniFile);
        return self::detach($oIni);
    }

    /**
     * detach 
     * 
     * @param array $array 
     * @static
     * @access private
     * @return array
     */
    private static function detach($array) {
        $returnArray = array();
        foreach($array as $key => $var) {
            if(is_array($var)) {
                $returnArray[$key] = self::detach($var);
                continue;
            } 
            $sepOffset = strpos($key,'.');
            if($sepOffset !== false) {
                $keyArr = str_replace('.','"]["',$key);
                eval("\$returnArray[\"{$keyArr}\"]='{$var}';");
            } else {
                $returnArray[$key] = $var;
            }
        }
        return $returnArray;
    }

    /**
     * load configuration file
     * 
     * @access public
     * @return void
     */
    private function loadCfg($file) {
        $oIni = parse_ini_file(dirname(__FILE__). '/default.ini');
        if(file_exists($file)) {
            $userConfig =  parse_ini_file($file);
            $oIni = array_replace_recursive($oIni,$userConfig);
        }
        self::$_CFG = self::detach($oIni);
    }
    
    /**
     * get object of all configuration option
     * 
     * @access public
     * @return Object
     */
    public static function CFG() {
        $conf = self::singleton();
        return $conf->getCfg();
    }
    
    /**
     * get the value of configuration option 
     * 
     * @param string $name
     * @return string
     */
    public function iniGet($name) {
        if(isset(self::$_CFG[$name])) return self::$_CFG[$name];
        return null;
    }
    
    /**
     * get object of all configuration option
     * 
     * @return Object
     */
    public function getCfg() {
        if(is_object(self::$_CFGOBJ)) return self::$_CFGOBJ;
        self::$_CFGOBJ =  array2object(self::$_CFG);
        return self::$_CFGOBJ;
    }
    
    /**
     * get array of all configuration option
     * 
     * @return array
     */
    public function getCfgArr() {
        return self::$_CFG;
    }

}


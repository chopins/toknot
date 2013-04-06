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
    protected function __construct() {
        $this->loadCfg();
    }
    public static function singleton() {
        return parent::__singleton();
    }

    /**
     * parse_ini 
     * 
     * @param string $iniFile 
     * @static
     * @access public
     * @return array
     */
    public static function parse_ini($iniFile) {
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
            $sep_offset = strpos($key,'.');
            if($sep_offset !== false) {
                $key_arr = str_replace('.','"]["',$key);
                eval("\$returnArray[\"{$key_arr}\"]='{$var}';");
            } else {
                $returnArray[$key] = $var;
            }
        }
        return $returnArray;
    }

    /**
     * load configuration
     * 
     * @access public
     * @return void
     */
    private function loadCfg() {
        $oIni = parse_ini_file(__X_FRAMEWORK_ROOT__ . '/toknot.def.ini');
        $app_config = __X_APP_DATA_DIR__ .'/conf/'.__X_APP_USER_CONF_FILE_NAME__;
        if(file_exists($app_config)) {
            $user_config =  parse_ini_file($app_config);
            $oIni = array_replace_recursive($oIni,$user_config);
        }
        self::$_CFG = self::detach($oIni);
    }
    public static function CFG() {
        $conf = self::singleton();
        return $conf->get_cfg();
    }
    public function ini_var($name) {
        if(isset(self::$_CFG[$name])) return self::$_CFG[$name];
        return null;
    }
    public function get_cfg() {
        if(is_object(self::$_CFGOBJ)) return self::$_CFGOBJ;
        self::$_CFGOBJ =  array2object(self::$_CFG);
        return self::$_CFGOBJ;
    }
    public function get_cfg_arr() {
        return self::$_CFG;
    }

}

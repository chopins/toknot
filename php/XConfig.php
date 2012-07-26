<?php

/**
 * XConfig 
 * 
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
final class XConfig {
    private static $_CFG = null;
    private static $xconfig_instance = null;
    private static $_CFGOBJ = null;
    private function __construct() {
    }
    public static function singleton() {
        $class_name = __CLASS__;
        if(self::$xconfig_instance && self::$xconfig_instance instanceof $class_name) {
            return self::$xconfig_instance;
        }
        self::$xconfig_instance = new $class_name; 
        self::$xconfig_instance->load_cfg();     
        return self::$xconfig_instance;
    }
    /**
     * load configuration
     * 
     * @access public
     * @return void
     */

    private function load_cfg() {
        self::$_CFG = parse_ini_file(__X_FRAMEWORK_ROOT__ . '/toknot.def.ini');
        $app_config = __X_APP_DATA_DIR__ .'/conf/'.__X_APP_USER_CONF_FILE_NAME__;
        if(file_exists($app_config)) {
            $user_config =  parse_ini_file($app_config);
            foreach($user_config as $key => $var) {
                if(is_array($var)) {
                    self::$_CFG[$key] += $var;
                } else {
                    self::$_CFG[$key] = $var;
                }
            }
        }
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

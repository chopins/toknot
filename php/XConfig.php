<?php

/**
 * XConfig 
 * 
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
final class XConfig extends XObject {
    private static $_CFG = null;
    private static $_CFGOBJ = null;
    protected function __construct() {}
    public static function singleton() {
        return parent::__singleton(array('XConfig','load_cfg'));
    }
    /**
     * load configuration
     * 
     * @access public
     * @return void
     */

    protected static function load_cfg() {
        self::$_CFG = parse_ini_file(__X_FRAMEWORK_ROOT__ . '/toknot.def.ini');
        $app_config = __X_APP_DATA_DIR__ .'/conf/'.__X_APP_USER_CONF_FILE_NAME__;
        if(file_exists($app_config)) {
            $user_config =  parse_ini_file($app_config);
            self::$_CFG = array_replace_recursive(self::$_CFG,$user_config);
        }
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

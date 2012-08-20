<?php
/**
 * Toknot
 *
 * XDbConnect
 *
 * PHP version 5.3
 * 
 * @package Config
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */

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
    protected function __construct() {
        $this->load_cfg();
    }
    public static function singleton() {
        return parent::__singleton();
    }

    /**
     * parse_ini 
     * 
     * @param string $ini_file 
     * @static
     * @access public
     * @return array
     */
    public static function parse_ini($ini_file) {
        $o_ini = parse_ini_file($ini_file);
        return self::detach($o_ini);
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
        $return_array = array();
        foreach($array as $key => $var) {
            if(is_array($var)) {
                $return_array[$key] = self::detach($var);
                continue;
            } 
            $sep_offset = strpos($key,'.');
            if($sep_offset !== false) {
                $key_arr = str_replace('.','"]["',$key);
                eval("\$return_array[\"{$key_arr}\"]='{$var}';");
            } else {
                $return_array[$key] = $var;
            }
        }
        return $return_array;
    }

    /**
     * load configuration
     * 
     * @access public
     * @return void
     */
    private function load_cfg() {
        $o_ini = parse_ini_file(__X_FRAMEWORK_ROOT__ . '/toknot.def.ini');
        $app_config = __X_APP_DATA_DIR__ .'/conf/'.__X_APP_USER_CONF_FILE_NAME__;
        if(file_exists($app_config)) {
            $user_config =  parse_ini_file($app_config);
            $o_ini = array_replace_recursive($o_ini,$user_config);
        }
        self::$_CFG = self::detach($o_ini);
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

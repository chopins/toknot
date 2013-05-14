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
use Toknot\Di\ArrayObject;

final class ConfigLoader extends Object {

    private static $_CFG = null;

    protected function __construct() {
        $file = __DIR__ . '/default.ini';
        self::$_CFG = new ArrayObject;
        self::loadCfg($file);
    }

    public static function singleton() {
        return parent::__singleton();
    }
    public static function CFG() {
        return self::$_CFG;
    }

    /**
     * detach 
     * 
     * @param array $array 
     * @static
     * @access private
     * @return array
     */
    private static function detach(array $array) {
        return new ArrayObject($array);
    }

    /**
     * load configuration file
     * 
     * @access public
     * @return void
     */
    public static function loadCfg($file) {
        if (file_exists($file)) {
            $userConfig = parse_ini_file($file, true);
            $userConfig = self::detach($userConfig);
            self::$_CFG->replace_recursive($userConfig);
        }
        return self::$_CFG;
    }

}


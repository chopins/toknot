<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Di\Object;

class ViewCache extends Object {

    public static $cacheEffective = false;
    private static $cacheFile = '';
    private static $renderer = null;
    private static $displayMethod = 'display';

    public static function registerDisplayHandle($method) {
        self::$displayMethod = $method;
    }

    public static function outPutCache() {
        call_user_func(array(self::$renderer,  self::$displayMethod), self::$cacheFile);
    }
    public static function setRenderer($object) {
        self::$renderer = $object;
    }

    public static function setCacheFile($file) {
        self::$cacheFile = $file;
    }
}
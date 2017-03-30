<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 */

namespace Toknot\Boot;

/**
 * Description of GlobalFilter
 *
 * @author chopin
 */
class GlobalFilter {

    private static $phpfilter = true;
    private static $phpSupportVar = [];

    const INPUT_GET = 'GET';
    const INPUT_POST = 'POST';
    const INPUT_COOKE = 'COOKE';
    const INPUT_SERVER = 'SERVER';
    const INPUT_ENV = 'ENV';
    const FILTER_DEFAULT = 'FILTER_UNSAFE_RAW';
    const FILTER_UNSAFE_RAW = 'FILTER_UNSAFE_RAW';
    const FILTER_VALIDATE_EMAIL = 'FILTER_VALIDATE_EMAIL';
    const FILTER_VALIDATE_INT = 'FILTER_VALIDATE_INT';
    const FILTER_VALIDATE_FLOAT = 'FILTER_VALIDATE_FLOAT';
    const FILTER_VALIDATE_URL = 'FILTER_VALIDATE_URL';
    const FILTER_VALIDATE_IP = 'FILTER_VALIDATE_IP';
    const FILTER_SANITIZE_XSS = 'FILTER_SANITIZE_XSS';

    public static function unavailablePHPFilter() {
        if (!self::$phpfilter) {
            return;
        }
        self::$phpfilter = false;
        self::$phpSupportVar['GET'] = $_GET;
        self::$phpSupportVar['POST'] = $_POST;
        self::$phpSupportVar['COOKIE'] = $_COOKIE;
        self::$phpSupportVar['ENV'] = $_ENV;
    }

    public static function isExternal($type, $key) {
        if (self::$phpfilter) {
            $type = constant("INPUT_$type");
            return filter_has_var($type, $key);
        }
        if ($type == self::INPUT_SERVER) {
            return getenv($key, true) ? true : false;
        }
        return isset(self::$phpSupportVar[$type][$key]);
    }

    public static function filter($type, $key, $filter = self::FILTER_DEFAULT) {
        if ($filter == self::FILTER_SANITIZE_XSS) {
            $value = self::filter($type, $key);
            return self::filterXSS($value);
        }
        if (self::$phpfilter) {
            $type = constant("INPUT_$type");
            return filter_input($type, $key, constant($filter));
        }
        if (!isset(self::$phpSupportVar[$type][$key])) {
            return null;
        }

        $value = $type == self::INPUT_SERVER ? getenv($key) : self::$phpSupportVar[$type][$key];
        switch ($filter) {
            case self::FILTER_VALIDATE_EMAIL:
                if (self::isEmail($value)) {
                    return $value;
                }
                return false;
            case self::FILTER_VALIDATE_FLOAT:
                if (self::isFloat($value)) {
                    return (float) $value;
                }
                return false;
            case self::FILTER_VALIDATE_INT:
                if (self::isInt($value)) {
                    return (int) $value;
                }
                return false;
            case self::FILTER_VALIDATE_IP:
                if (self::isIp($value)) {
                    return $value;
                }
                return false;
            case self::FILTER_VALIDATE_URL:
                if (self::isUrl($value)) {
                    return $value;
                }
                return false;
            case self::FILTER_UNSAFE_RAW:
            default:
                return $value;
        }
    }

    public static function env($key) {
        $res = PHP_MIN_VERSION > 6 ? getenv($key, true) : getenv($key);
        if (!$res) {
            return self::filter(self::INPUT_SERVER, $key);
        }
        return $res;
    }

}

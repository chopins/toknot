<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Permission;

use Toknot\Boot\TKObject;

class Permission extends TKObject {

    public static $defaultBase = 36;

    public static function generateRolePermission(&$allRolePerms, &$index = 0) {
        $index = self::find0($allRolePerms);
        $allPerms = self::strval(Permission::setbit($allPerms, $index));
        return self::strval(gmp_pow(2, $index));
    }

    public function byteLength($number) {
        $gmp = self::init($number);
        $number = self::strval($gmp);
        return log($number, 2);
    }

    public static function init($number) {
        return gmp_init($number, self::$defaultBase);
    }

    public static function find0($number) {
        $gmp = self::init($number);
        return gmp_scan0($gmp, 0);
    }

    public static function find1($number) {
        $gmp = self::init($number);
        return gmp_scan1($gmp, 0);
    }

    public static function setbit($number, $index, $setOn = true) {
        $gmp = self::init($number);
        gmp_setbit($gmp, $index, $setOn);
        return $gmp;
    }

    public static function newBit($index) {
        $hold = self::setbit(0, $index);
        return self::strval($hold);
    }

    public static function setDefaultBase($base = 36) {
        self::$defaultBase = $base;
    }

    public static function remove($hold, $mask) {
        self::initOp($hold, $mask);
        if ($mask == 0) {
            return self::strval($hold);
        }
        $index = strlen(gmp_strval($mask, 2)) - 1;
        gmp_setbit($hold, $index, false);
        return self::strval($hold);
    }

    public static function has($hold, $mask) {
        self::initOp($hold, $mask);
        if ($mask == 0) {
            return true;
        }
        return gmp_and($hold, $mask) != 0;
    }

    public static function set($hold, $mask) {
        self::initOp($hold, $mask);
        $res = gmp_or($hold, $mask);
        return self::strval($res);
    }

    protected static function initOp(&$left, &$right) {
        $left = self::init($left);
        $right = self::init($right);
    }

    public static function strval($number) {
        return gmp_strval($number, self::$defaultBase);
    }

}

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Toknot\Boot\Object;

/**
 * QueryFilter
 *
 * @author chopin
 */
class QueryHelper extends Object {

    public static function andX() {
        return ['&&', func_get_args()];
    }

    public static function orX() {
        return ['&&', func_get_args()];
    }

    public static function equal($key, $expr) {
        return [$key, $expr, '='];
    }

    public static function gt($key, $expr) {
        return [$key, $expr, '>'];
    }

    public static function lt($key, $expr) {
        return [$key, $expr, '<'];
    }

    public static function ge($key, $expr) {
        return [$key, $expr, '>='];
    }

    public static function le($key, $expr) {
        return [$key, $expr, '<='];
    }

    public static function in($key, $params) {
        return [$key, $params, 'IN'];
    }

    public static function out($key, $params) {
        return [$key, $params, 'NOTIN'];
    }

    public static function isNull($key) {
        return [$key, '', 'NULL'];
    }

    public static function notNull($key) {
        return [$key, '', 'NOTNULL'];
    }

    public static function add($left, $right) {
        return ['+', $left, $right];
    }

    public static function minus($left, $right) {
        return ['-', $left, $right];
    }

    public static function mul($left, $right) {
        return ['*', $left, $right];
    }

    public static function div($left, $right) {
        return ['/', $left, $right];
    }

    public static function set($key, $expr) {
        return [$key => $expr];
    }

    public static function setEqualFeild($key, $feild) {
        return [$key => [$feild]];
    }

    public function __call($method, $args = []) {
        self::invokeStatic(__CLASS__, $method, $args);
    }

    public function __callStatic($name, $args = []) {
        return [$args[0], $args[1], $name];
    }

}

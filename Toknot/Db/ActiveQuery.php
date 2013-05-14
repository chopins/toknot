<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Config\ConfigData;

class ActiveQuery {

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';
    const READ = 'SELECT';
    const UPDATE = 'UPDATE';
    const COLUMN_SET = 'SET';
    const INSERT = 'INSERT';
    const DELETE = 'DELETE';
    const JOIN = 'JOIN';
    const CREATE = 'CREATE';
    const LOGICAL_AND = 'AND';
    const LOGICAL_OR = 'OR';
    const EQUAL = '=';
    const LESS_THAN = '<';
    const GREATER_THAN = '>';
    const LESS_OR_EQUAL = '<=';
    const GREATER_OR_EQUAL = '>=';
    const SHOW_TABLES = 'SHOW TABLES';

    public static function select($tableName, $field = '*') {
        return "SELECT $field FROM $tableName";
    }
    public static function field($array) {
        return implode(',', $array);
    }

    public static function update($tableName) {
        return "UPDATE $tableName SET";
    }

    public static function delete($tableName) {
        return "DETELE FROM $tableName";
    }

    public static function leftJoin($tableName, $alias) {
        return " LEFT JOIN $tableName AS $alias";
    }

    public static function on($key1, $key2) {
        return " ON $key1=$key2";
    }

    public static function alias($name, $alias) {
        return " $name AS $alias";
    }

    public static function transformDsn($dsn) {
        $config = new ConfigData;
        $str = strtok($dsn, ':');
        $config->type = $str;
        while ($str) {
            $str = strtok('=');
            $this->$str = strtok(';');
        }
        return $config;
    }

    public static function showColumnList($tableName) {
        return "SHOW COLUMNS FROM $tableName";
    }

    public static function limit($start, $limit = null) {
        if ($limit === null) {
            return " LIMIT {$start}";
        } else {
            $limit = (int) $limit;
            return " LIMIT {$start},{$limit}";
        }
    }

    public static function order($order, $field) {
        if ($order == self::ASC) {
            return " ORDER BY $field ASC";
        } else {
            return " ORDER BY $field DESC";
        }
    }

    public static function where($sql = 1) {
        return " WHERE $sql";
    }

    public static function bindTableAlias($alias, $columnList) {
        return ' '.$alias . '.' . implode(", $alias.", $columnList);
    }

}
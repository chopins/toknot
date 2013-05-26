<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Di\ArrayObject;
use Toknot\Di\DbTableObject;

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
    const FETCH_ASSOC = 'ASSOC';
    const FETCH_NUM = 'NUM';
    const FETCH_BOTH = 'BOTH';
    const FETCH_OBJ = 'OBJ';
    const DRIVER_MYSQL = 'mysql';
    const DRIVER_SQLITE = 'sqlite';

    private static $dbDriverType = self::DRIVER_MYSQL;

    public static function setDbDriverType($type) {
        self::$dbDriverType = $type;
    }
    public static function getDbDriverType() {
        return self::$dbDriverType;
    }
    public static function parseSQLiteColumn($sql) {
        $feildInfo = strtok($sql, '(');
        $feildInfo = strtok(')');
        $columnList = array();
        $columnList[] = strtok($feildInfo, ' ');
        $pro  = strtok(',');
        while($pro) {
           $feild = strtok(' ');
           if(!$feild) {
               break;
           }
           $columnList[] = $feild;
           $pro  = strtok(',');
        }
        return $columnList;
    }

    public static function createTable($tableName) {
        if(self::$dbDriverType == self::DRIVER_MYSQL) {
            return "CREATE TABLE IF NOT EXISTS `$tableName`";
        }
        return "CREATE TABLE $tableName";
    }

    public static function setColumn(&$table) {
        $columnList = $table->showSetColumnList();
        $sqlList = array();
        foreach ($columnList as $columnName => $column) {
            $sqlList[$columnName] = " $columnName {$column->type}";
            if($column->length >0) {
                $sqlList[$columnName] .= "($column->length)";
            }
            if ($column->isPK) {
                $sqlList[$columnName] .= ' primary key';
            }
            if ($column->autoIncrement) {
                $sqlList[$columnName] .= ' autoincrement';
            }
        }
        return '('. implode(',', $sqlList) . ')';
    }

    public static function select($tableName, $field = '*') {
        return "SELECT $field FROM $tableName";
    }

    public static function bindParams($params, $sql) {
        if (empty($params)) {
            return $sql;
        }
        foreach ($params as &$v) {
            $v = addslashes($v);
            $v = "'$v'";
        }
        return str_replace('?', $params, $sql);
    }

    public static function field(array $array) {
        return implode(',', $array);
    }

    public static function update($tableName) {
        return "UPDATE $tableName SET";
    }

    public static function set($field) {
        $setList = array();
        foreach ($field as $key => $val) {
            $setList = "$key='" . addslashes($val) . "'";
        }
        return ' ' . implode(',', $setList);
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
        $config = new ArrayObject;
        $str = strtok($dsn, ':');
        $config->type = $str;
        while ($str) {
            $str = strtok('=');
            $config->$str = strtok(';');
        }
        return $config;
    }

    public static function showColumnList($tableName) {
        if(self::$dbDriverType == self::DRIVER_SQLITE) {
            return "SELECT * FROM sqlite_master WHERE type='table' AND name='$tableName'";
        }
        return "SHOW COLUMNS FROM $tableName";
    }

    public static function showTableList($database = null) {
        if (self::$dbDriverType == self::DRIVER_SQLITE) {
            return "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name";
        }
        if ($database == null) {
            return "SHOW TABLES";
        }
        return "SHOW TABLES FROM $database";
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
        if($field == NULL) {
            return '';
        }
        if ($order == self::ORDER_ASC) {
            return " ORDER BY $field ASC";
        } else {
            return " ORDER BY $field DESC";
        }
    }

    public static function where($sql = 1) {
        return " WHERE $sql";
    }

    public static function bindTableAlias($alias, $columnList) {
        return ' ' . $alias . '.' . implode(", $alias.", $columnList);
    }

    public static function insert($tableName, $field) {
        $field = implode(',', keys($field));
        foreach ($field as &$v) {
            $v = addslashes($v);
        }
        $values = "'" . implode("','", $field) . "'";
        return "INSERT INTO $tableName ($field) VALUES($values)";
    }

}
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
    const ORDER_ASC= 'ASC';
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
    
    public static function select() {
        
    }

    public static function transformDsn($dsn) {
        $config = new ConfigData;
        $str = strtok($dsn, ':');
        $config->type = $str;
        while($str) {
            $str = strtok('=');
            $this->$str = strtok(';');
        }
        return $config;
    }
    public static function showTableList() {
        return 'SHOW TABLES';
    }
    public static function showColumnList() {
        return 'SHOW COLUMNS FROM';
    }
    public static function updateColumn() {
        return 'UPDATE %s SET %s=%s WHERE %s=%s LIMIT 1';
    }
    public static function limit($start, $limit=null) {
        if($limit === null ) {
            return "LIMIT {$start}";
        } else {
            $limit = (int) $limit;
            return "LIMIT {$start},{$limit}";
        }
    }
    public static function order($order) {
        if($order == self::ASC) {
            return 'ORDER BY %s ASC';
        } else {
            return 'ORDER BY %s DESC';
        }
    }
}
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
 * QueryBulideer
 *
 * @author chopin
 */
class QueryBulider extends Object {

    /**
     *
     * @var \Doctrine\DBAL\Query\QueryBuilder
     */
    protected $builder = null;
    protected static $paramIndex = 0;

    public function __construct($conn) {
        $this->builder = $conn->createQueryBuilder();
        self::$paramIndex++;
    }

    public function initQuery($type, $tableName) {
        $fnc = $type == 'select' ? 'from' : $type;
        $this->builder->$fnc($tableName);
        return $this;
    }

    public function where($where) {
        if ($where instanceof QueryWhere) {
            $where = $where->getWhereSQL();
        }
        $this->builder->where($where);
    }

    public function expr() {
        return new QueryWhere();
    }

    public function checkParamType($value) {
        if (is_null($value)) {
            return \PDO::PARAM_NULL;
        } elseif (is_numeric($value)) {
            return \PDO::PARAM_INT;
        }
        return \PDO::PARAM_STR;
    }

    public function setParamter($cols, $value, $type = null) {
        $type = $type ? $type : $this->checkParamType($value);
        $idx = self::$paramIndex++;
        $placeholder = ":w{$idx}{$cols}";
        $this->builder->setParameter($placeholder, $value, $type);
        return $placeholder;
    }

    public function batchSet($values) {
        foreach ($values as $cols => $value) {
            $c = new QueryColumn($cols, $this);
            $c->set($value);
        }
        return $this;
    }

    public function batchEq($values) {
        $eqs = [];
        foreach ($values as $cols => $value) {
            $c = new QueryColumn($cols, $this);
            $c->eq($value);
            $eqs[] = $c->getSQL();
        }
        return $eqs;
    }

    public function set($k, $v) {
        $this->builder->set($k, $v);
    }

    public function quote($input, $type = null) {
        $type || $type = is_numeric($input) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
        return $this->expr()->literal($input, $type);
    }

    public function __call($name, $argv) {
        return self::callMethod(count($argv), $name, $argv, $this->builder);
    }

}

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

    /**
     *
     * @var Table
     */
    protected $table;

    public function __construct($conn, Table $table) {
        $this->builder = $conn->createQueryBuilder();
        $this->table = $table;
        self::$paramIndex++;
    }

    public function initQueryType($type, $tableName) {
        $type = strtolower($type);
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
            $c = new QueryColumn($cols, $this, $this->table);
            $c->set($value);
        }
        return $this;
    }

    public function batchEq($values) {
        $eqs = [];
        foreach ($values as $cols => $value) {
            $c = new QueryColumn($cols, $this, $this->table);
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

    public function insertOrUpdate($values) {

        $table = $this->table->getTableName();

        $params = [];
        $pk = $this->table->getPrimaryKeyName();
        $keyOn = $keyName = '';
        $iscopk = $this->table->isCompositePrimaryKey();
        if ($iscopk) {
            $keyName = $keyOn = [];
        }

        foreach ($values as $key => $v) {
            $params[$key] = $this->setParamter($key, $v);
            $hv = $this->setParamter($key, $v);
            if ($iscopk && in_array($key, $pk)) {
                $keyOn[] = "$key=$hv";
                $keyName[] = $key;
            } elseif ($pk == $key) {
                $keyOn = "$key=$hv";
            } else {
                $update[$key] = $v;
            }
        }
        
        $insertKeyHit = implode(',', array_keys($params));
        $insertHit = implode(',', $params);

        if ($keyName != $pk) {
            return "INSERT INTO $table ($insertKeyHit) VALUES ($insertHit)";
        }

        if ($iscopk) {
            $keyOn = implode(',', $keyOn);
        }
        $pkStr = implode(',', $pk);
        $updateHit = implode(',', $this->batchEq($update));

        $sqls = ['mysql' => "INSERT INTO $table ($insertKeyHit) VALUES ($insertHit) ON DUPLICATE KEY UPDATE $updateHit",
            'sqlserver' => "MERGE INTO $table WITH (HOLDLOCK) USING (SELECT 1) ON ($keyOn) WHEN MATCHED THEN UPDATE SET $updateHit WHEN NOT MATCHED THEN INSERT ($insertKeyHit) VALUES ($insertHit)",
            'oracle' => "MERGE INTO $table USING  DUAL ON ($keyOn) WHEN MATCHED THEN UPDATE SET $updateHit WHEN NOT MATCHED THEN INSERT ($insertKeyHit) VALUES ($insertHit)",
            'postgresql' => "INSERT INTO $table ($insertKeyHit) VALUES ($insertHit) ON CONFLICT($pkStr) DO UPDATE $updateHit",
            'drizzle' => "INSERT INTO $table ($insertKeyHit) VALUES ($insertHit) ON DUPLICATE KEY UPDATE $updateHit",
            'sqlazure' => "MERGE INTO $table AS TAR WITH (HOLDLOCK) USING (SELECT 1) ON ($keyOn) ON ($keyOn) WHEN MATCHED THEN UPDATE SET $updateHit WHEN NOT MATCHED THEN INSERT ($insertKeyHit) VALUES ($insertHit)",
            'sqlanywhere' => "INSERT INTO $table ($insertKeyHit) ON EXISTING UPDATE ON VALUES ($insertHit) "];

        $plat = strtolower($this->builder->getConnection()->getDatabasePlatform()->getName());
        if (!isset($sqls[$plat])) {
            throw new BaseException("$plat not support 'IF EXISTS' check SQL");
        }

        return $sqls[$plat];
    }

    public function __call($name, $argv) {
        return self::callMethod(count($argv), $name, $argv, $this->builder);
    }

}

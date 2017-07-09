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

    public function cols($cols) {
        return $this->table->cols($cols);
    }

    public function initQueryType($type, $tableName) {
        $type = strtolower($type);
        $fnc = $type == 'select' ? 'from' : $type;
        $this->builder->$fnc($tableName, $this->table->alias());
        return $this;
    }

    public function where($where) {
        if ($where instanceof QueryWhere) {
            $where = $where->getSQL();
        }
        $this->builder->where($where);
    }

    public function expr() {
        return new QueryWhere();
    }

    public function checkParamType($cols, $value) {
        if (is_null($value)) {
            return \PDO::PARAM_NULL;
        }
        return $cols->getDBBindingType();
    }

    public function setParamter($cols, $value) {
        if (!$cols instanceof QueryColumn) {
            $cols = $this->cols($cols);
        }
        $type = $this->checkParamType($cols, $value);
        $idx = self::$paramIndex++;
        $placeholder = ":pws{$idx}" . $cols->getColumnName();
        $this->builder->setParameter($placeholder, $value, $type);
        return $placeholder;
    }

    public function batchSet($values) {
        if ($values instanceof QueryColumn) {
            return $this;
        }
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
            $params[$key] = $this->setParamter($this->cols($key), $v);
            $hv = $this->setParamter($key, $v);
            if ($iscopk && in_array($key, $pk)) {
                $keyOn[] = "$key=$hv";
                $keyName[] = $key;
            } elseif ($pk == $key) {
                $keyOn = "$key=$hv";
                $keyName = $pk;
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
            $pkStr = implode(',', $pk);
        } else {
            $pkStr = $pk;
        }

        $updateHit = implode(',', $this->batchEq($update));

        $sqls = ['mysql' => "INSERT INTO $table ($insertKeyHit) VALUES ($insertHit) ON DUPLICATE KEY UPDATE $updateHit",
            'mssql' => "MERGE INTO $table WITH (HOLDLOCK) USING (SELECT 1) ON ($keyOn) WHEN MATCHED THEN UPDATE SET $updateHit WHEN NOT MATCHED THEN INSERT ($insertKeyHit) VALUES ($insertHit)",
            'oracle' => "MERGE INTO $table USING  DUAL ON ($keyOn) WHEN MATCHED THEN UPDATE SET $updateHit WHEN NOT MATCHED THEN INSERT ($insertKeyHit) VALUES ($insertHit)",
            'postgresql' => "INSERT INTO $table ($insertKeyHit) VALUES ($insertHit) ON CONFLICT($pkStr) DO UPDATE $updateHit",
            'drizzle' => "INSERT INTO $table ($insertKeyHit) VALUES ($insertHit) ON DUPLICATE KEY UPDATE $updateHit",
            'sqlanywhere' => "INSERT INTO $table ($insertKeyHit) ON EXISTING UPDATE ON VALUES ($insertHit) "];
        $platform = $this->builder->getConnection()->getDatabasePlatform();

        $plat = strtolower($platform->getName());
        if (!isset($sqls[$plat])) {
            throw new BaseException("$plat not support insert on duplicate key update");
        }
        
        if ($plat == 'mssql') {
            $version = $this->builder->getConnection()->getDriver()->version;
            if (version_compare($version, '9.00.1399','<=')) {
                throw new BaseException("sql server 2008 previous not support insert on duplicate key update");
            }
        } elseif($plat == 'postgresql') {
            $version = $this->builder->getConnection()->getDriver()->version;
            if(version_compare($version, '9.5','<=')) {
                throw new BaseException("only postgresql version >= 9.5 support insert on duplicate key update");
            }
        }

        return $sqls[$plat];
    }
    

    public function replace($table) {
        $sqls = ['mysql' => "REPLACE INTO $table ",
            'drizzle' => "REPLACE INTO $table ",
        ];
        $plat = strtolower($this->builder->getConnection()->getDatabasePlatform()->getName());

        if (!isset($sqls[$plat])) {
            throw new BaseException("$plat not support replace into");
        }
        return $sqls[$plat];
    }

    public function executeQuery($sql) {
        $res = $this->getConnection()->executeQuery($sql, $this->getParameters(), $this->getParameterTypes());
        $this->builder->getSQL();
        return $res;
    }

    public function executeUpdate($sql) {
        $res = $this->getConnection()->executeUpdate($sql, $this->getParameters(), $this->getParameterTypes());
        $this->builder->getSQL();
        return $res;
    }

    public function __call($name, $argv = []) {
        return self::callMethod($this->builder, $name, $argv);
    }

}

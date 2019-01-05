<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use Toknot\Boot\Kernel;
use Toknot\Lib\Model\Database\DB;
use Toknot\Lib\Model\Database\QueryBuild;
use Toknot\Lib\Exception\ErrorReportHandler;

abstract class TableModel {

    /**
     *
     * @var \Toknot\Lib\Model\Database\DB
     */
    protected $db = null;
    protected $selectFeild = '';
    protected $tableName = '';
    protected $columnList = [];
    protected $columnDefault = [];
    protected $keyName = '';
    protected $joinOn = [];
    protected $alias = '';
    protected $cols = [];
    protected $unique = [];
    protected $index = [];
    protected $mulUnique = [];
    protected $ai = false;
    protected $where = null;
    public $casVerCol = '_cas_ver';

    /**
     * 
     * @param string $dbkey
     */
    final public function __construct($dbkey = '') {
        $this->db = DB::instance($dbkey);
        $this->query = null;
    }

    /**
     * 
     * @return \Toknot\Lib\Model\Database\DB
     */
    public function db() {
        return $this->db;
    }

    /**
     * 
     * @return string
     */
    public function getRawSql() {
        return $this->lastSql;
    }

    /**
     * 
     * @return array
     */
    public function getCols() {
        return $this->cols;
    }

    /**
     * 
     * @return array
     */
    public function getColumns() {
        return $this->columnList;
    }

    /**
     * 
     * @return mixed
     */
    public function getColumnDefault() {
        return $this->columnDefault;
    }

    /**
     * 
     * @return bool
     */
    public function isAutoIncrement() {
        return $this->ai;
    }

    /**
     * 
     * @return string
     */
    public function getSelectFeild() {
        return $this->selectFeild;
    }

    /**
     * 
     * @return string
     */
    public function getKey() {
        return $this->keyName;
    }

    /**
     * 
     * @return array
     */
    public function getUnique() {
        return $this->unique;
    }

    
    /**
     * 
     * @return array
     */
    public function getMulUnique() {
        return $this->mulUnique;
    }

    /**
     * 
     * @return array
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * 
     * @return int
     */
    public function lastInsertId() {
        return $this->db->lastInsertId();
    }

    /**
     * 
     * @return \PDOStatement
     */
    public function executeSelectOrUpdate(QueryBuild $queryBuild) {
        $sql = $queryBuild->getSQL();
        $sth = $this->db->prepare($sql);
        $bindParameter = $queryBuild->getParameterValue();
        foreach ($bindParameter as $i => $bind) {
            if (is_array($bind)) {
                $param = $bind[1];
                $isNum = $bind[0];
            } else {
                $param = $bind;
                $isNum = false;
            }
            if (is_numeric($i)) {
                $sth->bindValue($i + 1, $param, DB::PARAM_STR);
            } else {
                $res = $sth->bindValue($i, $param, $isNum ? DB::PARAM_INT : DB::PARAM_STR);
            }
        }
        $res = $sth->execute();
        if (!$res) {
            $errInfo = $sth->errorInfo();
            $errData = [$errInfo[1], "SQLSTATE[$errInfo[0]] $errInfo[2]", $sql, $bindParameter];
            $handle = new ErrorReportHandler($errData);
            return $handle->throwException();
        }
        $queryBuild->cleanBindParameter();
        return $sth;
    }

    /**
     * 
     * @param string $name
     * @return int
     */
    public function lastId($name = '') {
        return $this->db->lastInsertId($name);
    }

    /**
     * 
     * @return \Toknot\Lib\Model\Database\QueryBuild
     */
    public function query() {
        return new QueryBuild($this);
    }

    /**
     * 
     * @return QueryBuild
     */
    public function newQuery() {
        $query = new QueryBuild($this);
        $query->cleanBindParameter();
        return $query;
    }

    public function endQuery() {
        QueryBuild::clearAllBindParameter();
    }

    /**
     * 
     * @return string
     */
    public function tableName() {
        return $this->tableName;
    }

    public function quote($string) {
        $this->db->quote($string);
    }

    /**
     * 
     * @return string
     */
    public function getAlias() {
        return $this->alias;
    }

    /**
     * 
     * @param string $alias
     */
    public function setAlias($alias = '') {
        $this->alias = $alias ? $alias : $this->tableName;
    }

    /**
     * 
     * @param mixed $where
     * @return $this
     */
    public function where($where) {
        $this->where = $where;
        return $this;
    }

    /**
     * find one row
     * 
     * @param mixed $id
     * @return array
     */
    public function one($id) {
        $query = $this->query();
        if (is_array($id)) {
            $and = $query->onAnd();
            foreach ($id as $col => $v) {
                $and->arg($query->col($col)->eq($v));
            }

            return $query->where($and)->limit(1)->row();
        }
        return $query->findOne($id);
    }

    /**
     * one row ActiveRecord
     * 
     * @param mixed $id
     * @return \Toknot\Lib\Model\Database\ActiveRecord
     */
    public function findOne($id) {
        $list = $this->one($id);
        if (!$list) {
            return null;
        }
        return new ActiveRecord($this, $list);
    }

    /**
     * empty ActiveRecord
     * 
     * @return \Toknot\Lib\Model\Database\ActiveRecord
     */
    public function idler() {
        return new ActiveRecord($this, []);
    }

    /**
     * count all row number
     * 
     * @param mix $where
     * @return int
     */
    public function count($where = null) {
        $query = $this->query();
        $query->where($where);
        return $query->count();
    }

    /**
     * find all row
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findAll($limit, $offset = 0) {
        $query = $this->query();
        return $query->range($offset, $limit)->all();
    }

    /**
     * find row by greater than id
     * 
     * @param int $id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findGTId($id, $limit, $offset = 0) {
        $query = $this->query();
        $exp = $query->col($this->keyName)->gt($id);
        return $query->where($exp)->range($offset, $limit)->all();
    }

    /**
     * find row by less than id
     * 
     * @param int $id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findLTId($id, $limit, $offset = 0) {
        $query = $this->query();
        $exp = $query->col($this->keyName)->lt($id);
        return $query->where($exp)->range($offset, $limit)->all();
    }

    /**
     * update by id when cas version not change
     * 
     * @param array $param
     * @param int $id
     * @param int $casValue
     * @param int $newValue
     * @return int
     */
    public function casUpdateById(array $param, $id, $casValue, $newValue) {
        $query = $this->query();
        $exp1 = $query->col($this->keyName)->eq($id);
        $exp2 = $query->col($this->casVerCol)->eq($casValue);
        $exp = $query->onAnd($exp1, $exp2);
        $param[$this->casVerCol] = $newValue;
        return $query->where($exp)->range(0, 1)->update($param);
    }

    /**
     * update by id
     * 
     * @param array $param
     * @param int $id
     * @param int $where
     * @return int
     */
    public function updateById(array $param, $id, $where = '') {
        $query = $this->query();
        $exp1 = $query->col($this->keyName)->eq($id);
        $filter = $exp1;
        if ($where) {
            $and = $query->onAnd();
            $and->arg($exp1);
            $and->arg($where);
            $filter = $and;
        }
        return $query->where($filter)->range(0, 1)->update($param);
    }

    /**
     * delete row by id
     * 
     * @param int $id
     * @return int
     */
    public function deleteById($id) {
        $query = $this->query();
        $exp = $query->key()->eq($id);
        return $query->where($exp)->range(0, 1)->delete();
    }

    /**
     * update a feild value by id
     * 
     * @param int $id
     * @param string $setCol
     * @param mixed $set
     */
    public function setById($id, $setCol, $set) {
        $query = $this->query();
        $exp1 = $query->key()->eq($id);
        $exp2 = $query->col($setCol)->eq($set);
        $query->where($exp1)->range(0, 1)->update($exp2);
    }

    /**
     * multitple table query,default is left join query
     * 
     * @param array $table
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function multiAll(array $table, $limit, $offset = 0) {
        $this->setAlias();
        $selfQuery = $this->query();
        if (is_array($table)) {
            foreach ($table as $type => $t) {
                if (is_numeric($type)) {
                    $this->multiJoin($t, $selfQuery);
                } else {
                    $this->multiJoin($t, $selfQuery, $type);
                }
            }
        } else {
            $this->multiJoin($table, $selfQuery);
        }
        return $selfQuery->range($offset, $limit)->all();
    }

    /**
     * insert data
     * 
     * @param array $param          the first row data, if not key with query column name will use table defined feild
     * @param array $otherData      the other row data, must align with first param
     * @return int
     */
    public function insert($param, $otherData = []) {
        return $this->query()->insert($param, $otherData);
    }

    /**
     * save data, if has exists of key will update, otherwise insert data
     * 
     * @param array $param      save data, one value is pairs key/value map column-name/value
     * @param bool $autoUpate   whether auto update, if true will check unique key whether seted and exec update
     *                          if false only check parimary key
     * @param int $casVer       current cas ver
     * @param int $newVer       update new cas
     * @return int
     */
    public function save(array $param, $autoUpate = true, $casVer = 0, $newVer = 0) {
        $keys = array_keys($param);
        if (isset($param[$this->keyName]) && ($this->ai || $autoUpate)) {
            $idValue = Kernel::pullOut($param, $this->keyName);
            if ($newVer && $casVer) {
                $this->casUpdateById($param, $idValue, $casVer, $newVer);
            } else {
                $this->updateById($param, $idValue);
            }
        } elseif ($autoUpate && ($ainter = array_intersect($keys, $this->unique))) {
            $query = $this->query();
            $and = $query->onAnd();
            foreach ($ainter as $col => $value) {
                $exp = $query->col($col)->eq($value);
                $and->arg($exp);
                unset($param[$col]);
            }
            if ($casVer && $newVer) {
                $and->arg($this->casExpression($query, $casVer));
                $param[$this->casVerCol] = $newVer;
            }
            return $query->where($and)->range(0, 1)->update($param);
        } else {
            if ($newVer) {
                $param[$this->casVerCol] = $newVer;
            }
            return $this->insert($param);
        }
    }

    /**
     * 
     * @param \Toknot\Lib\Model\Database\QueryBuild $query
     * @param int $casVer
     * @return \Toknot\Lib\Model\Database\Expression
     */
    public function casExpression(QueryBuild $query, $casVer) {
        return $query->col($this->casVerCol)->eq($casVer);
    }

    /**
     * 
     * @param \Toknot\Lib\Model\Database\TableModel $table
     * @param QueryBuild $selfQuery
     * @param string $type
     */
    protected function multiJoin(TableModel $table, QueryBuild $selfQuery, $type = QueryBuild::L_JOIN) {
        $table->setAlias();
        $exp = $table->query()->key()->eq($selfQuery->key());
        if ($type === QueryBuild::C_JOIN) {
            $selfQuery->join($table, $exp);
        } elseif ($type === QueryBuild::R_JOIN) {
            $selfQuery->rightJoin($table, $exp);
        } elseif ($type === QueryBuild::I_JOIN) {
            $selfQuery->innerJoin($table, $exp);
        } else {
            $selfQuery->leftJoin($table, $type, $exp);
        }
    }

}

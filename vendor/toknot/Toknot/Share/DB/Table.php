<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Toknot\Share\DB\DBA;
use Toknot\Exception\BaseException;
use Toknot\Share\DB\TableIterator;

namespace Toknot\Share\DB;

/**
 * Description of DBTable1
 *
 * @author chopin
 */
class Table extends TableIterator {

    /**
     * The table name
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * Table alias name
     *
     * @var string
     */
    private $tableAlias = '';
    protected $tableStructure = [];

    /**
     * The primary key name
     *
     * @var string
     */
    protected $primaryKeyName;
    private $isCompositePrimaryKey = false;
    protected $columnSql = '';
    private $tmpColumnSql = '';
    protected $dbconnect = null;
    protected $namespace = '';

    /**
     *
     * @var QueryBulider
     */
    protected $qr = null;
    protected $statement = null;
    protected $lastSql = '';
    protected $joinTable = [];

    final public function __construct($conn, $alias = '') {
        $this->dbconnect = $conn;
        $this->tableAlias = $alias;
        $this->checkCompositePrimaryKey();
    }

    final public function __get($name) {
        if ($this->hasColumn($name)) {
            $name = strtolower(preg_replace('/([A-Z])/', "_$1", lcfirst($name)));
            return $this->cols($name);
        }
        throw BaseException::undefinedProperty($this, $name);
    }

    public function setTableAlias($alias) {
        $this->tableAlias = $alias;
        return $this;
    }

    public function getTableName() {
        if ($this->namespace) {
            return $this->namespace . '.' . $this->tableName;
        }
        return $this->tableName;
    }

    public function getTableAlias($require = true) {
        return $this->tableAlias ? $this->tableAlias : ($require ? $this->getTableName() : '');
    }

    public function alias($alias = '') {
        if ($alias) {
            return $this->setTableAlias($alias);
        }
        return $this->getTableAlias();
    }

    public function getPrimaryKeyName() {
        return $this->primaryKeyName;
    }

    public function pk() {
        return $this->getPrimaryKeyName();
    }

    public function checkCompositePrimaryKey() {
        $keys = explode(',', $this->primaryKeyName);
        if (count($keys) > 1) {
            $this->primaryKeyName = $keys;
            $this->isCompositePrimaryKey = true;
        } else {
            $this->primaryKeyName = $this->primaryKeyName;
            $this->isCompositePrimaryKey = false;
        }
    }

    public function chkCpk() {
        return $this->checkCompositePrimaryKey();
    }

    public function isCompositePrimaryKey() {
        return $this->isCompositePrimaryKey;
    }

    public function isCpk() {
        return $this->isCompositePrimaryKey();
    }

    /**
     * check current table whther has specify column
     * 
     * @param string $column
     * @return boolean
     */
    public function hasColumn($column) {
        return isset($this->tableStructure['column'][$column]);
    }

    public function getColumnSql() {
        $columnSql = $this->tmpColumnSql ? $this->tmpColumnSql : $this->columnSql;
        $this->tmpColumnSql = '';
        return $columnSql;
    }

    public function setColumnSql($column, $tableAlias = '') {
        if (!$tableAlias && $this->tableAlias) {
            $tableAlias = $this->tableAlias;
        } else if ($tableAlias) {
            $this->alias($tableAlias);
        }
        $glue = !$tableAlias ? ', ' : ", $tableAlias.";
        $this->tmpColumnSql = $tableAlias . (is_array($column) ? implode($glue, $column) : $column);
        return $this;
    }

    public function getDBName() {
        return DBA::single()->getDatabase();
    }

    public function autoNamespace() {
        $this->namespace = DBA::single()->getDatabase();
    }

    /**
     * Get current table info
     * 
     * @return array
     */
    final public function getTableStructure() {
        return $this->tableStructure;
    }

    /**
     * 
     * @return Toknot\Share\DB\QueryBuilder
     */
    final protected function builder() {
        if (!$this->qr) {
            $this->qr = new QueryBulider($this->dbconnect, $this);
        }
        return $this;
    }

    final public function getBuilder() {
        return $this->qr;
    }

    /**
     * 
     * @param string $type
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    final protected function initQuery($type) {
        $this->qr->initQueryType($type, $this->getTableName());
        return $this;
    }

    /**
     * execute a sql from QueryBuilder and return result resources
     * 
     * @param int $limit
     * @param int $start
     * @return $this
     */
    public function execute($limit = 50, $start = 0) {
        $this->limit($limit, $start);
        $this->statement = $this->qr->execute();
        return $this;
    }

    /**
     * set query offset
     * 
     * @param int $limit
     * @param int $start
     * @return $this
     */
    public function limit($limit, $start = 0) {
        $this->qr->setFirstResult($start);
        $this->qr->setMaxResults($limit);
        return $this;
    }

    public function query($sql) {
        $this->builder();
        return $this->qr->executeQuery($sql);
    }

    public function get($limit = 100, $offset = 0, $fetchMode = \PDO::FETCH_ASSOC) {
        $this->execute($limit, $offset);
        return $this->statement->fetchAll($fetchMode);
    }

    public function getRow($fetchMode = \PDO::FETCH_ASSOC) {
        return $this->statement->fetch($fetchMode);
    }

    /**
     * 
     * @return QueryWhere
     */
    public function filter() {
        $this->builder();
        return new QueryWhere($this, $this->qr);
    }

    public function cols($columnName) {
        $this->builder();
        return new QueryColumn($columnName, $this->qr, $this);
    }

    /**
     * select data
     * 
     * @param string $where
     * @param int $limit
     * @param int $start
     * @return  $this
     * @before $this->setColumn()
     */
    public function select($where = '') {
        $columnSql = $this->getColumnSql();
        if ($this->joinTable) {
            foreach ($this->joinTable as $table) {
                $columnSql .= ',' . $table->getColumnSql();
            }
        }

        $this->builder()->initQuery(__FUNCTION__);
        $this->qr->select($columnSql);
        if ($where) {
            $this->qr->where($where);
        }
        $this->lastSql = $this->qr->getSQL();
        $this->joinTable = [];
        return $this;
    }

    /**
     * delete data
     * 
     * @param string $where
     * @param int $limit
     * @param int $start
     * @return int
     */
    public function delete($where, $limit = 0, $start = 0) {
        $this->builder()->initQuery(__FUNCTION__);
        $this->qr->where($where);
        if ($limit) {
            $this->limit($limit, $start);
        }
        $this->lastSql = $this->qr->getSQL();

        return $this->qr->execute();
    }

    /**
     * update data
     * 
     * @param array $values         
     *                              
     * @param string $where  
     * @param int $limit
     * @param int $start
     * @return int
     */
    public function update($values, $where, $limit = 0, $start = 0) {
        $this->builder()->initQuery(__FUNCTION__);
        $this->qr->batchSet($values);
        if ($where) {
            $this->qr->where($where);
        }
        if ($limit) {
            $this->limit($limit, $start);
        }
        $this->lastSql = $this->qr->getSQL();
        $this->joinTable = [];
        return $this->qr->execute();
    }

    /**
     * insert data
     * 
     * @param array $value
     * @return int
     */
    public function insert($value) {
        $this->builder();
        $params = [];
        foreach ($value as $key => $v) {
            $params[$key] = $this->qr->setParamter($key, $v);
        }
        $this->initQuery(__FUNCTION__);
        $this->qr->values($params);

        $this->lastSql = $this->qr->getSQL();
        $this->qr->execute();
        return $this->lastId();
    }

    public function findKeyRow($keyValue) {
        if (is_array($keyValue)) {
            $where = $this->filter()->andX($keyValue);
        } else if ($this->isCompositePrimaryKey()) {
            $pkFilter = [];
            $args = func_get_args();
            foreach ($this->primaryKeyName as $i => $k) {
                $pkFilter[] = $this->filter()->cols($k)->eq($args[$i]);
            }
            $where = $this->filter()->andX($pkFilter);
        } else {
            $where = $this->filter()->cols($this->primaryKeyName)->eq($keyValue);
        }
        return $this->select($where)->execute(1)->getRow();
    }

    /**
     * get count
     * 
     * @param string $where
     * @param string $key
     * @return int
     */
    public function count($where = '', $key = '') {
        $this->builder()->initQuery('SELECT');

        $ck = $key ? $key : ($this->key && !$this->isCompositePrimaryKey ? $this->key : '*');
        $this->qr->select("COUNT($ck) AS _cnt");
        if ($where) {
            $this->qr->where($where);
        }
        $this->qr->execute();
        $res = $this->getRow();
        return $res['_cnt'];
    }

    public function lastId() {
        return $this->dbconnect->lastInsertId();
    }

    public function getLastSQL() {
        return $this->lastSql;
    }

    public function insertSelect($subSelect) {
        if (is_subclass_of($subSelect, __CLASS__)) {
            $sql = $subSelect->getLastSQL();
            $this->qr->setParameters($subSelect->getBuilder()->getParameters(), $subSelect->getBuilder()->getParameterTypes());
        } else {
            $sql = $subSelect;
        }
        $subSql = '(' . $sql . ')';
        $this->builder()->initQuery('INSERT');
        $sql = $this->lastSql . '(' . $this->tmpColumnSql . ')' . $subSql;
        return $this->qr->executeQuery($sql);
    }

    public function againSelect($where, $feild = []) {
        if ($this->qr->getType() != DBA::SELECT) {
            throw new BaseException('can not found first selct query');
        }

        $subSql = '(' . $this->qr->getSQL() . ')';
        $this->builder();
        $this->qr->from($subSql);
        $column = empty($feild) ? '*' : implode(',', $feild);
        $this->qr->select($column)->where($where);
        return $this;
    }

    /**
     * save data when exists primary key update
     * 
     * @param array $data
     * @return int
     */
    public function save($data) {
        $this->builder();
        $this->lastSql = $this->qr->insertOrUpdate($data);
        $this->qr->executeQuery($this->lastSql);
        return $this->lastId();
    }

    /**
     * get list from database
     * 
     * @param string|array $where
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getList($where, $limit = 20, $start = 0) {
        return $this->select($where)->get($limit, $start);
    }

    /**
     * get list from database and use ASC order by key
     * 
     * @param string|array $where
     * @param string $orderby       order by key name
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getAscList($where, $orderby, $limit = 20, $start = 0) {
        return $this->select($where)->orderBy('asc', $orderby)
                        ->get($limit, $start);
    }

    /**
     * get list from database and use DESC order by key
     * 
     * @param string|array $where
     * @param string $orderby
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getDescList($where, $orderby, $limit = 20, $start = 0) {
        return $this->select($where)->orderBy('desc', $orderby)
                        ->get($limit, $start);
    }

    /**
     * group query and get list
     * 
     * @param string|array $where
     * @param string $group     group by key name
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function getGroupList($where, $group, $limit = 20, $start = 0) {
        return $this->select($where)->groupBy($group)->get($limit, $start);
    }

    /**
     * set order by key
     * 
     * @param string $sort
     * @param string $order
     * @return $this
     */
    public function orderBy($sort, $order = null) {
        $this->qr->orderBy($sort, $order);
        return $this;
    }

    /**
     * set group by key
     * 
     * @param string $key
     * @return $this
     */
    public function groupBy($key) {
        $this->qr->groupBy($key);
        return $this;
    }

    /**
     * set haveing key
     * 
     * @param string $clause
     * @return $this
     */
    public function having($clause) {
        $this->qr->having($clause);
        return $this;
    }

    /**
     * select at left join 
     * 
     * @param Toknot\Share\DB\Table $table
     * @param array $on
     * @param array|string $where
     * @return $this
     */
    public function leftJoin($table, $on) {
        return $this->join($table, $on, 'left');
    }

    /**
     * select at right join
     * 
     * @param Toknot\Share\DB\Table $table
     * @param array $on  the value smaliar 
     *                      [$column1,$column2,$expr] or mulit-dimensional-array
     * @return $this
     */
    public function rightJoin($table, $on) {
        return $this->join($table, $on, 'right');
    }

    /**
     * select at inner join
     * 
     * @param Toknot\Share\DB\Table|array $table
     * @param array $on
     * @param array|string $where
     * @return $this
     */
    public function innerJoin($table, $on) {
        return $this->join($table, $on, 'inner');
    }

    public function getJoinTable() {
        return $this->joinTable;
    }

    /**
     * 
     * @param Toknot\Share\DB\Table $tables
     * @param array $on
     * @param array $where
     * @param string $type
     * @return $this
     */
    protected function join($table, $on, $type = 'left') {
        $this->builder();
        $join = $this->getJoinFunc($type);
        $this->joinTable[] = $table;
        $this->qr->$join($this->getTableAlias(), $table->getTableName(), $table->getTableAlias(), $on);
        $otherJoin = $table->getJoinTable();
        if ($otherJoin) {
            foreach ($otherJoin as $other) {
                $queryPart = $other->getBuilder()->getQueryPart('join');
                $this->qr->add('join', $queryPart);
                $other->getBuilder()->getSQL();
            }
        }
        return $this;
    }

}

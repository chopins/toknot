<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use PDO;
use Toknot\Boot\Kernel;
use Toknot\Lib\Model\Database\TableModel;
use Toknot\Lib\Model\Database\Column;
use Toknot\Lib\Model\Database\Expression;
use Toknot\Lib\Model\Database\LogicalExpression;
use Toknot\Lib\Model\Database\FunctionExpression;
use Toknot\Lib\Model\Database\QueryExpression;

class QueryBuild {

    protected $lastSql = '';
    protected $queryResultStyle = PDO::FETCH_ASSOC;
    protected $queryResultFetchArgument = null;
    protected $queryResultClassObject = null;
    protected $group = [];
    protected $order = [];
    protected $offset = 0;
    protected $step = 10;
    protected $leftFeildsList = [];
    protected $valueData = [];
    protected $noBacktick = false;
    protected $whereSQL = '';
    protected $joinTable = [];
    protected $sqlType = '';
    protected $multiTable = false;
    protected $forceIndex = '';
    protected $useIndex = '';
    protected $ignoreIndex = '';
    protected static $queryParameters = [];
    protected $forUpdate = false;

    const SORT_ASC = ' ASC ';
    const SORT_DESC = ' DESC ';
    const BACKTICK = '`';
    const I_JOIN = ' INNER JOIN ';
    const L_JOIN = ' LEFT JOIN ';
    const R_JOIN = ' RIGHT_JOIN ';
    const C_JOIN = ' JOIN ';
    const SELECT = 'SELECT ';
    const UPDATE = 'UPDATE ';
    const INSERT = 'INSERT INTO ';
    const DELETE = 'DELETE FROM ';
    const INSERT_VALUE = ' VALUES ';
    const FORM = ' FROM ';
    const UPDATE_SET = ' SET ';
    const GROUP = ' GROUP BY ';
    const ORDER = ' ORDER BY ';
    const OFFSET = ' OFFSET ';
    const LIMIT = ' LIMIT ';
    const TABLE_AS = ' AS ';
    const JOIN_ON = ' ON ';
    const IN = ' IN';
    const NOT_IN = ' NOT IN ';
    const LKIE = ' LIKE ';
    const U_INDEX = ' USE IDEX';
    const F_INDEX = ' FORCE INDEX';
    const FOR_UPDATE = ' FOR UPDATE';
    const I_INDEX = ' IGNORE INDEX';
    const WHERE = ' WHERE ';

    /**
     *
     * @var \Toknot\Lib\Model\Database\TableModel
     */
    protected $tableModel = null;

    public function __construct(TableModel $tableModel) {
        $this->tableModel = $tableModel;
        $this->leftFeildsList = [];
        $this->joinTable = [];
        $this->group = [];
        $this->order = [];
        $this->valueData = [];
        $this->noBacktick = false;
        $this->multiTable = false;
        $this->forUpdate = false;
        $this->forSelect = false;
    }

    public function __get($name) {
        return $this->col($name);
    }

    public function clearInsertValue() {
        $this->valueData = [];
    }

    public function clearJoin() {
        $this->joinTable = [];
    }

    public function clearLeftFeilds() {
        $this->leftFeildsList = [];
    }

    public function __clone() {
        $this->cleanBindParameter();
    }

    public function __destruct() {
        $this->cleanBindParameter();
    }

    public function quote($value) {
        $this->tableModel->quote($value);
    }

    public function hasFeild($feild) {
        return in_array($feild, $this->tableModel->getColumns());
    }

    public function key() {
        return $this->col($this->tableModel->getKey());
    }

    public function col($name, $value = null) {
        if ($this->hasFeild($name)) {
            $col = new Column($name, $this->tableModel, $this, $this->tableModel->getCols()[$name]);
            if ($value !== null) {
                return $col->eq($value);
            }
            return $col;
        } else {
            throw new Exception("coulum $name not found in table $this->tableName");
        }
    }

    public function bindParameterValue($name, $value, $isNum = false) {
        if (is_numeric($name)) {
            throw new Exception('bind param name can not numeric');
        }
        self::$queryParameters[$name] = [$isNum, $value];
    }

    public function bindValue($value) {
        self::$queryParameters[] = $value;
    }

    public function getParameterValue() {
        return self::$queryParameters;
    }

    public function bindColParameterValue(Column $col, $value) {
        $key = Kernel::COLON . $col->getName() . count(self::$queryParameters);
        self::$queryParameters[$key] = [$col->isNumber(), $value];
        return $key;
    }

    public function cleanBindParameter() {
        self::$queryParameters = [];
    }

    public static function clearAllBindParameter() {
        self::$queryParameters = [];
    }

    /**
     * 
     * @param mix $feild    string of SQL expression, * , array, 
     *                      instance of Column, 
     *                      instance of FunctionExpression,
     *                      instance of Expression
     * @param \Toknot\Lib\Model\Database\TableModel $table
     * @return $this
     */
    public function select($feild = Kernel::STAR, $table = null) {
        $table = $table === null ? $this->tableModel : $table;
        if ($feild === Kernel::STAR) {
            if ($this->multiTable) {
                $this->multiTableSelectFeild($table);
            } else {
                $this->leftFeildsList[] = $table->getSelectFeild();
            }
        } elseif (is_string($feild)) {
            $this->leftFeildsList[] = $feild;
        } elseif (is_array($feild)) {
            foreach ($feild as $f) {
                if (is_string($f)) {
                    $this->leftFeildsList[] = $table->query()->col($f);
                } else {
                    $this->leftFeildsList[] = $f;
                }
            }
        } else {
            $this->leftFeildsList[] = $feild;
        }
        return $this;
    }

    public function forUpdate() {
        $this->forUpdate = true;
        return $this;
    }

    public function getSQL() {
        $sql = $this->buildQueryType();

        if ($this->sqlType !== self::INSERT) {
            $sql .= $this->buildGroup();
            $sql .= $this->buildOrder();
            $sql .= $this->buildOffset();
        }
        return $sql;
    }

    public function group($feild) {
        $this->group[$feild] = 1;
        return $this;
    }

    public function order($feild, $sort = self::SORT_ASC) {
        $this->order[$feild] = $sort;
        return $this;
    }

    public function offset($offset = 0) {
        $this->offset = $offset;
        return $this;
    }

    public function limit($step) {
        $this->step = $step;
        return $this;
    }

    public function onAnd(...$args) {
        return $this->logicalExpression(Kernel::L_AND, $args);
    }

    public function onOr(...$args) {
        return $this->logicalExpression(Kernel::L_OR, $args);
    }

    public function onXor(...$args) {
        return $this->logicalExpression(Kernel::L_XOR, $args);
    }

    public function eq($left = null, $right = null) {
        return $this->expression(Kernel::EQ, $left, $right);
    }

    public function lt($left = null, $right = null) {
        return $this->expression(Kernel::LT, $left, $right);
    }

    public function gt($left = null, $right = null) {
        return $this->expression(Kernel::GT, $left, $right);
    }

    public function le($left = null, $right = null) {
        return $this->expression(Kernel::LE, $left, $right);
    }

    public function ge($left = null, $right = null) {
        return $this->expression(Kernel::GE, $left, $right);
    }

    public function neq($left = null, $right = null) {
        return $this->expression(Kernel::NEQ, $left, $right);
    }

    public function lg($left = null, $right = null) {
        return $this->expression(Kernel::LG, $left, $right);
    }

    public function add($left = null, $right = null) {
        return $this->expression(Kernel::M_ADD, $left, $right);
    }

    public function sub($left = null, $right = null) {
        return $this->expression(Kernel::M_SUB, $left, $right);
    }

    public function mul($left = null, $right = null) {
        return $this->expression(Kernel::M_MUL, $left, $right);
    }

    public function div($left = null, $right = null) {
        return $this->expression(Kernel::M_DIV, $left, $right);
    }

    public function useIndex($indexs) {
        $this->useIndex = $this->func(self::U_INDEX, $indexs);
        return $this;
    }

    public function forceIndex($indexs) {
        $this->forceIndex = $this->func(self::F_INDEX, $indexs);
        return $this;
    }

    public function ignoreIndex($indexs) {
        $this->ignoreIndex = $this->func(self::F_INDEX, $indexs);
        return $this;
    }

    public function func($name, ...$args) {
        $exp = new FunctionExpression($name);
        $exp->args($args);
        return $exp;
    }

    public function join(TableModel $table, $type = self::C_JOIN, $on = '') {
        $this->multiTable = true;
        $this->joinTable[] = [$type, $table, $on];
        return $this;
    }

    /**
     * 
     * @param string|QueryExpression $condition 
     * @param mixed $value
     * @return $this
     */
    public function where($condition, $value = null) {
        if ($value === null && is_string($condition)) {
            $this->whereSQL = $condition;
        } elseif ($value !== null && is_string($condition)) {
            $col = $this->col($condition);
            $this->whereSQL = $col->eq($value);
        } elseif (is_subclass_of($condition, QueryExpression::class)) {
            $this->whereSQL = $condition->getExpression();
        } elseif ($condition !== null) {
            Kernel::runtimeException('unsupport condition in QueryBuild::where()', E_USER_WARNING);
        }
        return $this;
    }

    public function range($start, $limit) {
        $this->offset = $start;
        $this->step = $limit;
        return $this;
    }

    public function asArray() {
        $this->queryResultStyle = PDO::FETCH_ASSOC;
        return $this;
    }

    public function asNum() {
        $this->queryResultStyle = PDO::FETCH_NUM;
        return $this;
    }

    /**
     * 
     * @param int $style
     * @param mix $class
     * @param mix $args
     * @return $this
     */
    public function resultStyle($style, $class = null, $args = []) {
        $this->queryResultStyle = $style;
        $this->queryResultClassObject = $class;
        $this->queryResultFetchArgument = $args;
        return $this;
    }

    public function findOne($id) {
        $this->sqlType = self::SELECT;
        $keyName = $this->tableModel->getKey();
        if (!$keyName) {
            $unique = $this->tableModel->getUnique();
            $type = is_numeric($id) ? Kernel::T_NUMBER : Kernel::T_STRING;
            if (!$unique) {
                Kernel::runtimeException('table ' . $this->tableModel->tableName() . ' not exits key', E_USER_WARNING);
            }
            foreach ($unique as $col) {
                if ($this->col($col)->isNumber() && $type == 'number') {
                    $keyName = $col;
                } else {
                    $keyName = $col;
                }
            }
        }
        $this->where($keyName, $id);
        $this->limit(1);
        return $this->row();
    }

    public function count($feild = Kernel::STAR) {
        $this->sqlType = self::SELECT;
        $count = $this->func('COUNT', $feild);
        $this->select($count);
        $this->limit(1);
        return $this->tableModel->executeSelectOrUpdate($this)->fetchColumn(0);
    }

    public function sum($feild) {
        $this->sqlType = self::SELECT;
        $sum = $this->func('SUM', $feild);
        $this->select($sum);
        return $this->tableModel->executeSelectOrUpdate($this)->fetchColumn(0);
    }

    public function row() {
        $this->sqlType = self::SELECT;
        $sth = $this->tableModel->executeSelectOrUpdate($this);
        if ($this->queryResultClassObject === null) {
            $sth->setFetchMode($this->queryResultStyle);
        } elseif (is_int($this->queryResultClassObject) || is_object($this->queryResultClassObject)) {
            $sth->setFetchMode($this->queryResultStyle, $this->queryResultClassObject);
        } elseif (is_string($this->queryResultClassObject) && !is_numeric($this->queryResultClassObject)) {
            $sth->setFetchMode($this->queryResultStyle, $this->queryResultClassObject, $this->queryResultFetchArgument);
        }
        return $sth->fetch();
    }

    public function column($idx) {
        $this->resultStyle(PDO::FETCH_COLUMN, null, $idx);
        return $this->all();
    }

    public function all() {
        $this->sqlType = self::SELECT;
        $sth = $this->tableModel->executeSelectOrUpdate($this);
        if ($this->queryResultStyle === PDO::FETCH_COLUMN) {
            return $sth->fetchAll($this->queryResultStyle, $this->queryResultFetchArgument);
        } elseif ($this->queryResultStyle === PDO::FETCH_CLASS) {
            return $sth->fetchAll($this->queryResultStyle, $this->queryResultClassObject, $this->queryResultFetchArgument);
        } else {
            return $sth->fetchAll($this->queryResultStyle);
        }
    }

    public function update($param) {
        $this->sqlType = self::UPDATE;
        if (is_array($param)) {
            $this->setUpdate($param);
        } else {
            $this->leftFeildsList[] = $param;
        }
        $sth = $this->tableModel->executeSelectOrUpdate($this);
        return $sth->rowCount();
    }

    public function delete() {
        $this->sqlType = self::DELETE;
        $sth = $this->tableModel->executeSelectOrUpdate($this);
        return $sth->rowCount();
    }

    public function insert($firstRow, $multiValue = []) {
        $this->sqlType = self::INSERT;
        $this->setInsert($firstRow, $multiValue);
        $sth = $this->tableModel->executeSelectOrUpdate($this);
        return $sth->rowCount();
    }

    public function addBacktick($feild) {
        $bt = $this->noBacktick ? Kernel::NOP : Kernel::BACKTICK;
        return $bt . $feild . $bt;
    }

    public function leftJoin($table, $on = '') {
        $this->join($table, self::L_JOIN, $on);
        return $this;
    }

    public function rightJoin($table, $on = '') {
        $this->join($table, self::R_JOIN, $on);
        return $this;
    }

    public function innerJoin($table, $on = '') {
        $this->join($table, self::I_JOIN, $on);
        return $this;
    }

    protected function buildOffset() {
        $res = Kernel::NOP;
        if ($this->step > 0) {
            $res .= self::LIMIT . $this->step;
        }
        if ($this->sqlType === self::SELECT) {
            $this->offset = $this->offset > 0 ? $this->offset : 0;
            $res .= self::OFFSET . $this->offset;
        }
        return $res;
    }

    protected function buildGroup() {
        if (!$this->group) {
            return Kernel::NOP;
        }
        return self::GROUP . Kernel::LP . $this->buildFeild(array_keys($this->group)) . Kernel::RP;
    }

    protected function buildOrder() {
        if (!$this->order) {
            return Kernel::NOP;
        }
        $orders = [];
        foreach ($this->order as $f => $s) {
            if ($f instanceof Column) {
                $orders[] = $f . $s;
            } else {
                $orders[] = $this->addBacktick($f) . $s;
            }
        }
        return self::ORDER . join(Kernel::COMMA, $orders);
    }

    protected function multiTableSelectFeild($table) {
        $sep = Kernel::COMMA . Kernel::SP . Kernel::BACKTICK . $table->getAlias() . Kernel::BACKTICK . Kernel::DOT;
        $this->leftFeildsList[] = join($sep, $table->getColumns());
    }

    protected function buildJoin() {
        $sql = '';
        foreach ($this->joinTable as list($type, $table, $on)) {
            $tableName = $table->tableName();
            $sql .= $type . $tableName . self::TABLE_AS . $tableName;
            $sql .= self::JOIN_ON . $on;
            $this->multiTableSelectFeild($table);
        }
        return $sql;
    }

    protected function buildQueryType() {
        $tableName = $this->tableModel->tableName();
        if ($this->multiTable) {
            $tableName .= $this->buildJoin();
        }
        $where = Kernel::NOP;
        if ($this->whereSQL) {
            $where = self::WHERE . $this->whereSQL;
        }
        if ($this->sqlType == self::SELECT) {
            if (empty($this->leftFeildsList)) {
                $selectFeild = $this->tableModel->getSelectFeild();
            } else {
                $selectFeild = join(Kernel::COMMA, $this->leftFeildsList);
            }
            $hitIndex = $this->forceIndex . $this->ignoreIndex . $this->useIndex;

            $sql = self::SELECT . $selectFeild . self::FORM . $tableName . $hitIndex . $where;
            if ($this->forUpdate) {
                $sql .= self::FOR_UPDATE;
            }
        } elseif ($this->sqlType == self::UPDATE) {
            $fields = implode(Kernel::COMMA, $this->leftFeildsList);
            $sql = self::UPDATE . $tableName . self::UPDATE_SET . $fields . $where;
        } elseif ($this->sqlType == self::INSERT) {
            $valueList = join(Kernel::COMMA, $this->valueData);
            $sql = self::INSERT . $tableName . $this->leftFeildsList . self::INSERT_VALUE . $valueList;
        } else if ($this->sqlType == self::DELETE) {
            $sql = self::DELETE . $tableName . $where;
        }
        return $sql;
    }

    protected function buildFeild($feilds) {
        $bt = $this->noBacktick ? Kernel::NOP : Kernel::BACKTICK;
        $sep = $bt . Kernel::COMMA . $bt;
        return $bt . join($sep, $feilds) . $bt;
    }

    protected function expression($operator, $left = null, $right = null) {
        $exp = new Expression($operator);
        if ($left !== null) {
            $exp->left($left);
        }
        if ($right !== null) {
            $exp->right($right);
        }
        return $exp;
    }

    protected function logicalExpression($operator, $args) {
        $exp = new LogicalExpression($operator);
        $exp->args($args);
        return $exp;
    }

    protected function setUpdate($param) {
        foreach ($param as $key => $v) {
            if (is_scalar($v)) {
                $n = $this->col($key);
                $this->leftFeildsList[] = $n->set($v);
            } else {
                $this->leftFeildsList[] = $v;
            }
        }
    }

    protected function setInsert($first, $data = []) {
        $keys = array_keys($first);
        if (!is_numeric(current($keys))) {
            reset($keys);
            $this->leftFeildsList = Kernel::LP . $this->buildFeild($keys) . Kernel::RP;
        } else {
            $this->leftFeildsList = Kernel::LP . $this->tableModel->getSelectFeild() . Kernel::RP;
        }
        $len = count($first);
        $valueList = Kernel::LP . join(Kernel::COMMA, array_fill(0, $len, Kernel::QUTM)) . Kernel::RP;
        $this->valueData[] = $valueList;
        self::$queryParameters = array_merge(self::$queryParameters, array_values($first));

        foreach ($data as $v) {
            if (count($v) !== $len) {
                throw new Exception('insert multiple row must alignment of all row');
            }
            $this->valueData[] = $valueList;
            self::$queryParameters = array_merge(self::$queryParameters, $v);
        }
    }

}

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Db;

use Toknot\Db\DbCRUD;
use Toknot\Db\ActiveQuery;
use Toknot\Db\DatabaseObject;
use \InvalidArgumentException;
use Toknot\Db\Exception\DatabaseException;
use Toknot\Db\DbTableColumn;


final class DbTableObject extends DbCRUD {

    private $tableName;
    private $primaryName = null;
    private $dbObject = null;
    public $alias = null;
    private $columnList = array();
    private $columnValueList = array();
    public $where = 1;
    public $logical = ActiveQuery::LOGICAL_AND;

    public function __construct($tableName, DatabaseObject &$databaseObject, $newTable = false) {
        $this->tableName = $tableName;
        $this->dbObject = $databaseObject;
        $this->connectInstance = $databaseObject->connectInstance;
        $this->dbINSType = $databaseObject->dbINSType;
        if (!$newTable) {
            $this->showColumnList();
        }
        $this->order = ActiveQuery::ORDER_DESC;
        $this->orderBy = $this->primaryName;
    }

    public function setPropertie($name, $value) {
        if (in_array($name, $this->columnList)) {
            $this->columnValueList[$name] = new DbTableColumn($name, $this);
            $this->columnValueList[$name]->value = $value;
        } else {
            throw new DatabaseException("Table $this->tableName not exists Column {$name}");
        }
    }

    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
        if (in_array($name, $this->columnList)) {
            if (!isset($this->interatorArray[$name])) {
                $this->interatorArray[$name] = new DbTableColumn($name, $this);
            }
            return $this->interatorArray[$name];
        }
        if (!isset($this->columnValueList[$name])) {
            $this->columnValueList[$name] = new DbTableColumn($name, $this);
        }
        return $this->columnValueList[$name];
    }

    public function importColumnValue($array) {
        foreach ($array as $key => $var) {
            $this->setPropertie($key, $var);
        }
    }

    public function showSetColumnList() {
        return $this->columnValueList;
    }

    /**
     * Get table column list and set DbTableObject::$columnList
     */
    public function showColumnList() {
        $sql = ActiveQuery::showColumnList($this->tableName);
        $list = $this->readAll($sql);
        if (empty($list)) {
            return false;
        }
        if (ActiveQuery::getDbDriverType() == ActiveQuery::DRIVER_SQLITE) {
            $this->columnList = ActiveQuery::parseSQLiteColumn($list[0]['sql']);
            return $this->columnList;
        }
        foreach ($list as $field) {
            if (isset($field['Key']) && strtolower($field['Key']) == 'pri') {
                $this->primaryName = $field['Field'];
            }
            $this->columnList[] = $field['Field'];
        }
        return $this->columnList;
    }

    /**
     * Execute Create/INSERT opreater, before invoke, must set field value
     * 
     * <code>
     * $ar = new ActiveRecourd();
     * $db = $ar->connect();
     * $db->tableName->id = 1;
     * $db->tableName->name = 'the name';
     * $db->tableName->isLocked = 0;
     * $db->tableName->save();
     * </code>
     * 
     * array import like below:
     * <code>
     * $ar = new ActiveRecourd();
     * $db = $ar->connect();
     * $db->tableName->import($dataArray);
     * $db->tableName->save();
     * </code>
     * 
     * @return type
     * @throws DatabaseException
     */
    public function save() {
        if (empty($this->columnValueList)) {
            throw new DatabaseException("You must first set {$this->tableName}::\$columnValueList for column value");
        }
        $sql = ActiveQuery::insert($this->tableName, $this->columnValueList);
        $this->columnValueList = array();
        return $this->create($sql);
    }

    /**
     * Execute Update opreater by where statement, the where statement set by DbTableObject of $where
     * property before invoke, 
     * 
     * <code>
     * $ar = new ActiveRecourd();
     * $db = $ar->connect();
     * $db->tableName->name = 'the new name'; //update field value
     * $db->tableName->nameStatus = '1'; //update field value
     * $db->tableName->where = 'id=? AND isLocked = ?'; //must set where
     * $db->tableName->updateByWhere(array(1, 0));
     * </code>
     * 
     * @param array $params Options of the parameter of a element bound in the SQL of where statement of parameter
     * @param integer $start update start row number, default is 0
     * @param integer $limit update limit number, default is 1
     * @return int  return the opreater affected row of number
     * @throws DatabaseException
     */
    public function updateByWhere(array $params = array(), $start = 0, $limit = 1) {
        if ($this->where === 1) {
            throw new DatabaseException("Must first set {$this->tableName}::\$where");
        }
        if (empty($this->columnValueList)) {
            throw new DatabaseException("Must first set {$this->tableName}::\$columnValueList for update column");
        }
        $sql = ActiveQuery::update($this->tableName);
        $sql .= ActiveQuery::set($this->columnValueList);
        $this->columnValueList = array();
        $sql .= ActiveQuery::where($this->where);
        $sql .= ActiveQuery::limit($start, $limit);
        return $this->update($sql, $params);
    }

    /**
     * Execute Update opreater by the table primary key
     * 
     * @param mixed $pkValue The value of the table primary key
     * @param integer $start   update start row number, default is 0, when $condition is set ActiveQuery::EQUAL,
     *                       the set is invaild
     * @param integer $limit   limit number default is 1, when $condition is set ActiveQuery::EQUAL,
     *                       the set is invaild
     * @param string $condition Options of default is {@see ActiveQuery::EQUAL} The value use the
     *                         {@see ActiveQuery::EQUAL}{@see ActiveQuery::LESS_OR_EQUAL},
     *                         {@see ActiveQuery::LESS_THAN},{@see ActiveQuery::GREATER_OR_EQUAL},
     *                         {@see ActiveQuery::GREATER_THAN}
     * @return integer|boolean  return the update sql affected rows number or false on error
     * @throws DatabaseException
     * @throws InvalidArgumentException
     */
    public function updateByPK($pkValue, $start = 0, $limit = 1, $condition = ActiveQuery::EQUAL) {
        if (empty($this->columnValueList)) {
            throw new DatabaseException("Must first set {$this->tableName}::\$columnValueList for update column");
        }
        $sql = ActiveQuery::update($this->tableName);
        $sql .= ActiveQuery::set($this->columnValueList);
        $this->columnValueList = array();
        $sql .= ActiveQuery::where("{$this->primaryName} {$condition} ?");
        $sql .= ActiveQuery::conditionLimit($condition, $start, $limit);
        return $this->update($sql, array($pkValue));
    }

    /**
     * Execute Delete opreater by where statement, the where statement set by DbTableObject of $where
     * property before invoke
     * 
     * <code>
     * $ar = new ActiveRecourd();
     * $db = $ar->connect();
     * $db->tableName->where = 'id=? AND isLocked = ?'; //must set where
     * $db->tableName->deleteByWhere(array(1, 0));
     * </code>
     * 
     * @param array $params The parameter of a element bound in the SQL of where statement of parameter
     * @param integer $start delete start row number
     * @param integer $limit delete limit number
     * @return integer return the opreater affected row of number
     * @throws DatabaseException  if not set where property throw out
     */
    public function deleteByWhere(array $params = array(), $start = 0, $limit = 1) {
        if ($this->where === 1) {
            throw new DatabaseException("Must first set {$this->tableName}->where");
        }
        $sql = ActiveQuery::delete($this->tableName);
        $sql .= ActiveQuery::where($sql);
        $sql .= ActiveQuery::limit($start, $limit);
        return $this->delete($sql, $params);
    }

    /**
     * Execute Delete opreater by the primary key value of the table
     * 
     * @param mixed $pkValue The value of the table primary key
     * @param integer $start see {@see DbTableObject::updateByPK()}
     * @param integer $limit    see {@see DbTableObject::updateByPK()}
     * @param string $condition see {@see DbTableObject::updateByPK()}
     * @return integer return the opreater affected row of number
     * @throws DatabaseException
     */
    public function deleteByPk($pkValue, $start = 0, $limit = 1, $condition = ActiveQuery::EQUAL) {
        if ($this->where === 1) {
            throw new DatabaseException("Must first set {$this->tableName}->where");
        }
        $sql = ActiveQuery::delete($this->tableName);
        $sql .= ActiveQuery::where("{$this->primaryName} {$condition} ?");
        $sql .= ActiveQuery::conditionLimit($condition, $start, $limit);
        return $this->delete($sql, array($pkValue));
    }

    /**
     * Execute SELECT/READ opreater by where statement, the where tatement set by DbTableObject of $where
     * property before invoke
     * 
     * <code>
     * $ar = new ActiveRecourd();
     * $db = $ar->connect();
     * $db->tableName->where = 'time=? AND isLocked = ?'; //must set where
     * $db->tableName->findByWhere(array(time(), 0), 0, 10);
     * </code>
     * 
     * @param array $params The parameter of a element bound in the SQL of where statement of parameter
     * @param type $start
     * @param type $limit
     * @return mixed  return the opreater query of result
     * @throws DatabaseException if not set where property throw out
     */
    public function findByWhere(array $params = array(), $start = 0, $limit = null) {
        if ($this->where === 1) {
            throw new DatabaseException("Must first set {$this->tableName}->where");
        }
        $field = ActiveQuery::field($this->columnList);
        $sql = ActiveQuery::select($this->tableName, $field);
        $sql .= ActiveQuery::where($this->where);
        $sql .= ActiveQuery::order($this->order, $this->orderBy);
        $sql .= ActiveQuery::limit($start, $limit);
        return $this->readAll($sql, $params);
    }

    /**
     * Execute SELECT/READ opreate by primary key of the table, and not use $where property
     * 
     * default query use like below:
     * <code>
     * $ar = new ActiveRecourd();
     * $db = $ar->connect();
     * $db->tableName->findByPK(1);
     * </code>
     * 
     * other :
     * <code>
     * $ar = new ActiveRecourd();
     * $db = $ar->connect();
     * 
     * //find the primary key less or equal 5 and start of 0, limit is 5
     * $db->tableName->findByPK(5, 0, 5, ActiveQuery::LESS_OR_EQUAL);
     * </code>
     * 
     * @param string $pkValue the primary key value which is opreater of comparison 
     * @param int $start Options
     * @param int $limit Options
     * @param type $condition Options of default is {@see ActiveQuery::EQUAL} The value use the
     *                         {@see ActiveQuery::EQUAL}{@see ActiveQuery::LESS_OR_EQUAL},
     *                         {@see ActiveQuery::LESS_THAN},{@see ActiveQuery::GREATER_OR_EQUAL},
     *                         {@see ActiveQuery::GREATER_THAN}
     * @return return the opreater query of result
     * @throws InvalidArgumentException
     */
    public function findByPK($pkValue, $start = 1, $limit = null, $condition = ActiveQuery::EQUAL) {
        $field = ActiveQuery::field($this->columnList);
        $sql = ActiveQuery::select($this->tableName, $field);
        $sql .= ActiveQuery::where("{$this->primaryName} {$condition} ?");
        switch ($condition) {
            case ActiveQuery::EQUAL:
                return $this->readOne($sql, array($pkValue));
                break;
            case ActiveQuery::LESS_OR_EQUAL:
            case ActiveQuery::LESS_THAN:
            case ActiveQuery::GREATER_OR_EQUAL:
            case ActiveQuery::GREATER_THAN:
                $sql .= ActiveQuery::order($this->order, $this->orderBy);
                $sql .= ActiveQuery::limit($start, $limit);
                return $this->readAll($sql, array($pkValue));
            default :
                throw new InvalidArgumentException('Condition must be ActiveQuery defined opreater of comparison');
        }
    }

    /**
     * Find result by above context set column value, must first set column value
     * 
     * <code>
     * $ar = new ActiveRecord();
     * $db = $ar->connect();
     * $db->tableName->name = 'newName';
     * 
     * $db->tableName->findByAttr(10); // get name equal newName result of 10 rows
     * </code>
     * 
     * @param type $start
     * @param type $limit options of parameter for query limit
     * @param type $logical  options, the parameter of logical relationship in select where statement parameters
     *                        The value use the {@see ActiveQuery::LOGICAL_AND} or {@see ActiveQuery::LOCGICAL_OR}
     * @return mixed
     * @throws DatabaseException  if not set where property throw out
     * @throws InvalidArgumentException  if logical parameter set error
     */
    public function findByAttr($start, $limit = null, $logical = ActiveQuery::LOGICAL_AND) {
        if (empty($this->columnValueList)) {
            throw new DatabaseException("Must first set {$this->tableName}::\$columnValueList for update column");
        }
        if ($logical != ActiveQuery::LOGICAL_AND && $logical != ActiveQuery::LOGICAL_OR) {
            throw new InvalidArgumentException('must be ActiveQuery::LOGICAL_AND or ActiveQuery::LOGICAL_OR');
        }
        $field = ActiveQuery::field($this->columnList);
        $sql = ActiveQuery::select($this->tableName, $field);
        foreach ($this->columnValueList as $key => $var) {
            $var = addslashes($var);
            $where[] = " $key='$var' ";
        }
        $sql .= ActiveQuery::where(implode($logical, $where));
        $sql .= ActiveQuery::limit($start, $limit);
        return $this->readAll($sql);
    }

}
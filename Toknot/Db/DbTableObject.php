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

class DbTableObject extends DbCRUD {

    private $tableName;
    private $primaryName = null;
    private $dbObject = null;
    public $alias = null;
    private $columnList = array();
    private $columnValueList = array();
    public $where = 1;
    public $logical = ActiveQuery::LOGICAL_AND;

    public function __construct($tableName, DatabaseObject &$databaseObject) {
        $this->tableName = $tableName;
        $this->dbObject = $databaseObject;
        $this->connectInstance = $databaseObject->connectInstance;
        $this->showColumnList();
        $this->order = ActiveQuery::ORDER_DESC;
        $this->orderBy = $this->primaryName;
    }

    public function setPropertie($name, $value) {
        if (in_array($name, $this->columnList)) {
            $this->columnValueList[$name] = $value;
        }
    }

    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    public function showColumnList() {
        $sql = ActiveQuery::showColumnList();
        $sql .= $this->tableName;
        $list = $this->readAll($sql);
        foreach ($list as $field) {
            if (strtolower($field['Key']) == 'pri') {
                $this->primaryName = $field['Field'];
            }
            $this->columnList[] = $field['Field'];
        }
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
     * @return type
     * @throws DatabaseException
     */
    public function save() {
        if (empty($this->columnValueList)) {
            throw new DatabaseException("You must first set table column value");
        }
        $sql = ActiveQuery::insert($this->tableName, $this->columnValueList);
        return $this->create($sql);
    }

    /**
     * Execute Update opreater by where statement, the where statement set by DbTableObject of where
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
     * @return int  return the opreater affected row of number
     * @throws DatabaseException
     */
    public function updateByWhere(array $params = array()) {
        if ($this->where === 1) {
            throw new DatabaseException("Must first set {$this->tableName}->where");
        }
        $sql = ActiveQuery::update($this->tableName);
        $sql .= ActiveQuery::set($this->columnValueList);
        $sql .= ActiveQuery::where($this->where);
        return $this->update($sql, $params);
    }

    /**
     * Execute Delete opreater by where statement, the where statement set by DbTableObject of where
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
     * @return int return the opreater affected row of number
     * @throws DatabaseException  if not set where property throw out
     */
    public function deleteByWhere(array $params = array()) {
        if ($this->where === 1) {
            throw new DatabaseException("Must first set {$this->tableName}->where");
        }
        $sql = ActiveQuery::delete($this->tableName);
        $sql .= ActiveQuery::where($sql);
        return $this->delete($sql, $params);
    }

    /**
     * Execute SELECT/READ opreater by where statement, the where tatement set by DbTableObject of where
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
     * Execute SELECT/READ opreate by primary key of the table, and not use where property
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
     * @param type $condition Options of default is {@see ActiveQuery::EQUAL} The value use set 
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
                return $this->read($sql, array($pkValue));
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

}
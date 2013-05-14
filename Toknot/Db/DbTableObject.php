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

class DbTableObject extends DbCRUD {

    private $tableName;
    public $primaryName = null;
    private $dbObject = null;
    public $alias = null;
    private $columnList = array();
    private $columnValueList = array();
    public $where = '1';
    public $logical = ActiveQuery::LOGICAL_AND;

    public function __construct($tableName, DatabaseObject &$databaseObject) {
        $this->tableName = $tableName;
        $this->dbObject = $databaseObject;
        $this->connectInstance = $databaseObject->connectInstance;
        $this->showColumnList();
    }

    public function setPropertie($name, $value) {
        if (in_array($name, $this->$columnList)) {
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
            $this->$columnList[] = $field['Field'];
        }
    }

    public function query($sql) {
        $this->read($sql);
    }
    public function findByWhere($params= array(), $start =0, $limit = null) {
        $field = ActiveQuery::field($this->columnList);
        $sql = ActiveQuery::select($this->tableName,$field);
        $sql .= $this->where . ActiveQuery::limit($start, $limit);
        return $this->readAll($sql,$params);
    }

    public function findByPK($pkValue, $condition, $start=0, $limit = null) {
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
                $sql .= ActiveQuery::limit($start, $limit);
                return $this->readAll($sql, array($pkValue));
            default :
                throw new InvalidArgumentException('Condition must be ActiveQuery defined opreater of comparison');
        }
    }

}
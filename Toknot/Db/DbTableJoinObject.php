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
use Toknot\Db\DbTableJoinObject;
use \InvalidArgumentException;

class DbTableJoinObject extends DbCRUD{
    private $preONSQLs = array();
    private $preJONSQLs = array();
    private $firstTable  = null;
    public $where = 1;
    public function __construct(DbTableJoinObject $table1, DbTableObject $table2) {
        $tableList = func_get_args();
        foreach($tableList as $tableObject) {
            if(!$tableObject instanceof DbTableJoinObject) {
                throw new InvalidArgumentException('Must be DbTableJoinObject');
            }
            $this->interatorArray[$tableObject->tableName] = $tableObject;
        }
    }
    public function __get($name) {
        if(isset($this->interatorArray[$name])) {
            return $this->interatorArray[$name];
        }
    }
    protected function tableON($key1, $key2) {
        $keyList = func_get_args();
        $argc = func_num_args();
        if($argc % 2 !=0) {
            throw new InvalidArgumentException('Must be pairs');
        }
        $tableNum = count($this->interatorArray) - 1;
        if($tableNum != $argc/2) {
            throw new InvalidArgumentException('Args number invalid');
        }
        foreach($keyList as $key) {
            $nextKey = next($keyList);
            $this->preONSQLs[] = ActiveQuery::on($key, $nextKey);
        }
    }
    protected function tableJOIN() {
        $this->firstTable = array_shift($this->interatorArray);
        foreach($this->interatorArray as $table) {
            $this->preJONSQLs [] = ActiveQuery::leftJoin($table->tableName, $table->alias);
        }
    }
    protected function bulidSQL() {
        $partNum = count($this->preJONSQLs);
        $sql = '';
        $select = ActiveQuery::bindTableAlias($this->firstTable->alias, $this->firstTable->columnList);
        for($i=0;$i<$partNum;$i++) {
            $sql .= $this->preJONSQLs[$i];
            $sql .= $this->preONSQLs[$i];
            $field = ActiveQuery::bindTableAlias($this->interatorArray[$i]->alias, 
                                         $this->interatorArray[$i]->columnList);
        }
        $select = ActiveQuery::select($this->firstTable->tableName, $field) . ' AS ' . $this->firstTable->alias;
        return $select . $sql . ActiveQuery::where($this->where);
    }
    public function readLatest($start = 0, $limit = null) {
        $this->tableJOIN();
        $sql = $this->bulidSQL();
        $sql .= ActiveQuery::limit($start, $limit);
        $this->readAll($sql);
    }
}

?>

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use Iterator;
use ArrayAccess;
use Toknot\Boot\Kernel;

class ActiveRecord implements Iterator, ArrayAccess {

    protected $table = null;
    protected $currentRecord = [];
    protected $columns = [];
    protected $newRecordData = [];
    protected $idValue = '';
    protected $idName = '';
    protected $iteratorKey = '';

    public function __construct(TableModel $table, $record) {
        $this->table = $table;
        $this->currentRecord = $record;
        $this->columns = $this->table->getColumns();
        $this->initKey();
    }

    public function __get($name) {
        $name = Kernel::classToLower($name, Kernel::UDL);
        if (in_array($name, $this->columns)) {
            return $this->currentRecord[$name];
        }
        Kernel::runtimeException(get_class($this->table) . " not exits property $name", E_USER_NOTICE);
    }

    public function toArray() {
        return $this->currentRecord;
    }

    public function getId() {
        return $this->idValue;
    }

    public function idName() {
        return $this->idName;
    }

    public function __set($name, $value) {
        $name = Kernel::classToLower($name, Kernel::UDL);
        if (in_array($name, $this->columns)) {
            $this->newRecordData[$name] = $value;
            return;
        }
        Kernel::runtimeException(get_class($this->table) . " not exits property $name", E_USER_NOTICE);
    }

    public function columns() {
        return $this->columns;
    }

    public function isIdler() {
        return empty($this->currentRecord);
    }

    protected function initKey() {
        if ($this->table->getKey()) {
            $this->idName = $this->table->getKey();
            !$this->isIdler() && $this->idValue = $this->currentRecord[$this->idName];
        } elseif ($this->table->getUnique()) {
            $this->idName = $this->table->getUnique()[0];
            !$this->isIdler() && $this->idValue = $this->currentRecord[$this->idName];
        } elseif ($this->table->getMulUnique()) {
            $muUnique = $this->table->getMulUnique();
            $unique = current($muUnique);
            $this->idName = $unique;
            if (!$this->isIdler()) {
                foreach ($unique as $k) {
                    $this->idValue[$k] = $this->currentRecord[$k];
                }
            }
        } else {
            Kernel::runtimeException(get_class($this->table) . " not exits key or unique", E_USER_NOTICE);
        }
    }

    public function reset() {
        $this->newRecordData = [];
    }

    public function save($where = '') {
        if (empty($this->currentRecord)) {
            return $this->insert();
        } elseif (is_array($this->idValue)) {
            $query = $this->table->query();
            $and = $query->onAnd();
            foreach ($this->idValue as $col => $v) {
                $and->arg($query->col($col)->eq($v));
            }
            if ($where) {
                $and->arg($where);
            }
            return $query->where($and)->limit(1)->update($this->newRecordData);
        } else {
            return $this->table->updateById($this->newRecordData, $this->idValue, $where);
        }
    }

    public function lastInsertId() {
        return $this->table->db()->lastInsertId();
    }

    protected function insert() {
        $query = $this->table->query();
        $insert = [];
        foreach ($this->table->getColumns() as $col) {
            if (isset($this->newRecordData[$col])) {
                $insert[$col] = $this->newRecordData[$col];
                continue;
            }

            $cols = $query->col($col);
            if ($cols->isAutoIncrement()) {
                continue;
            }
            if (!$cols->hasDefault()) {
                $insert[$col] = $cols->guessInsertDefaultValue();
            }
        }

        return $query->insert($insert);
    }

    public function rewind() {
        reset($this->currentRecord);
    }

    public function current() {
        return current($this->currentRecord);
    }

    public function key() {
        $this->iteratorKey = key($this->currentRecord);
        return $this->iteratorKey;
    }

    public function next() {
        next($this->currentRecord);
    }

    public function valid() {
        return isset($this->currentRecord[$this->iteratorKey]);
    }

    public function offsetExists($offset) {
        if (in_array($offset, $this->columns)) {
            return true;
        }
        return false;
    }

    public function offsetGet($offset) {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value) {
        return $this->__set($offset, $value);
    }

    public function offsetUnset($offset) {
        Kernel::runtimeException('can not unset a property of table ' . get_class($this->table), E_USER_NOTICE);
    }

}

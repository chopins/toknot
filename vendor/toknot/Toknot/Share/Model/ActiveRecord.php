<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot\Model;

use Toknot\Share\DB\DBA;

/**
 * ActiveRecord
 *
 * @author chopin
 */
class ActiveRecord {

    protected $tableInstance = null;
    protected $sets = [];
    protected $key = '';
    protected $table = '';

    public function __construct($table) {
        $this->table = $table;
        $this->tableInstance = DBA::table($this->table);
        $this->tableInstance->primaryKey();
    }

    public function __set($name, $value) {
        $this->sets[$name] = $value;
    }

    public function save($key = null) {
        if (isset($key)) {
            $this->tableInstance->update($this->sets, [$this->key, $key]);
        } else {
            $this->tableInstance->insert($this->sets);
        }
        $this->sets = [];
    }

    public function query($sql, $where) {
        return $this->tableInstance->query($sql, $where);
    }

    public function tableName() {
        return $this->table;
    }

    public function increment($id, $feild, $step = 1) {
        return $this->tableInstance->update([$feild => ['+', $feild, $step]], [$this->key, $id]);
    }

    public function decrement($id, $feild, $step = 1) {
        return $this->tableInstance->update([$feild => ['-', $feild, $step]], [$this->key, $id]);
    }

    public function findOne($id) {
        return $this->tableInstance->getKeyValue($id);
    }

    public function delete($id) {
        return $this->tableInstance->delete([$this->key, $id]);
    }

    public function find($where, $limit, $start = 0) {
        return $this->tableInstance->iterator($where, $limit, $start);
    }

}

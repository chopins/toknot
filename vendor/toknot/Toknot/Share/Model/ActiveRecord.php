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

    /**
     * 
     * @param string $table
     */
    public function __construct($table) {
        $this->table = $table;
        $this->tableInstance = DBA::table($this->table);
        $this->tableInstance->primaryKey();
    }

    /**
     * 
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value) {
        $this->sets[$name] = $value;
    }

    /**
     * save or update data
     * 
     * @param string $key
     */
    public function save($key = null) {
        if (isset($key)) {
            $this->tableInstance->update($this->sets, [$this->key, $key]);
        } else {
            $this->tableInstance->insert($this->sets);
        }
        $this->sets = [];
    }

    /**
     * exec a query
     * 
     * @param string $sql
     * @param array $where
     * @return Toknot\Share\DB\DBTable
     */
    public function query($sql, $where) {
        return $this->tableInstance->query($sql, $where);
    }

    /**
     * get table name
     * 
     * @return string
     */
    public function tableName() {
        return $this->table;
    }

    /**
     * increment a feild by id
     * 
     * @param string $id
     * @param string $feild
     * @param int $step
     * @return int
     */
    public function incrementOne($id, $feild, $step = 1) {
        return $this->tableInstance->update([$feild => ['+', $feild, $step]], [$this->key, $id]);
    }

    public function increment($filter, $feild, $step = 1) {
        return $this->tableInstance->update([$feild => ['+', $feild, $step]], $filter);
    }

    /**
     * decrement a feild by id
     * 
     * @param string $id
     * @param string $feild
     * @param int $step
     * @return int
     */
    public function decrementOne($id, $feild, $step = 1) {
        return $this->tableInstance->update([$feild => ['-', $feild, $step]], [$this->key, $id]);
    }

    public function decrement($filter, $feild, $step = 1) {
        return $this->tableInstance->update([$feild => ['-', $feild, $step]], $filter);
    }

    /**
     * find row by id
     * 
     * @param string $id
     * @return array
     */
    public function findOne($id) {
        return $this->tableInstance->getKeyValue($id);
    }

    /**
     * delete row by id
     * 
     * @param string $id
     * @return int
     */
    public function deleteOne($id) {
        return $this->tableInstance->delete([$this->key, $id]);
    }

    public function delete($filter, $limit = 0) {
        return $this->tableInstance->delete($filter, $limit);
    }

    /**
     * get iterator of query result
     * 
     * @param array $filter
     * @param int $limit
     * @param int $start
     * @return Toknot\Share\DB\DBTable
     */
    public function find($filter, $limit, $start = 0) {
        return $this->tableInstance->iterator($filter, $limit, $start);
    }

    /**
     * get all result
     * 
     * @param array $filter
     * @param int $limit
     * @param int $start
     * @return array
     */
    public function findAll($filter, $limit, $start = 0) {
        return $this->tableInstance->getList($filter, $limit, $start);
    }

}

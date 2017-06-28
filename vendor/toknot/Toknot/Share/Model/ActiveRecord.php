<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Model;

use Toknot\Share\Model\CRUD;

/**
 * ActiveRecord
 *
 * @author chopin
 */
class ActiveRecord {

    protected $crud = null;
    protected $sets = [];
    protected $key = '';
    protected $tableName = '';
    protected $table = null;
    protected $maxLimit = 50;

    /**
     * 
     * @param string $tableName
     */
    public function __construct($tableName) {
        $this->tableName = $tableName;
        $this->crud = new CRUD($this->table);
        $this->table = $this->tableInstance->getTable();
        $this->key = $this->table->pk();
    }

    public function setMaxLimit($limit) {
        $this->maxLimit = $limit;
    }

    /**
     * 
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value) {
        $this->sets[$name] = $value;
    }

    public function cols($cols) {
        return $this->table->cols($cols);
    }

    /**
     * save or update data
     * 
     * @param string $key
     */
    public function save($key = null) {
        if (isset($key)) {
            $this->crud->updateByKey($this->sets, $key);
        } else {
            $this->crud->create($this->sets);
        }
        $this->sets = [];
    }

    /**
     * exec a query
     * 
     * @param string $sql
     */
    public function query($sql) {
        return $this->table->query($sql);
    }

    /**
     * get table name
     * 
     * @return string
     */
    public function tableName() {
        return $this->tableName;
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
        $filter = $this->table->buildKeyWhere($id);
        return $this->crud->update([$feild => $this->cols($feild)->add($step)], $filter, 1, 1);
    }

    public function increment($filter, $feild, $step = 1, $limit = 50, $start = 0) {
        return $this->crud->update([$feild => $this->cols($feild)->add($step)], $filter, $limit, $start);
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
        $filter = $this->table->buildKeyWhere($id);
        return $this->crud->update([$feild => $this->cols($feild)->mins($step)], $filter, 1, 1);
    }

    public function decrement($filter, $feild, $step = 1, $limit = 50, $start = 0) {
        return $this->crud->update([$feild => $this->cols($feild)->mins($step)], $filter, $limit, $start);
    }

    /**
     * find row by id
     * 
     * @param string $id
     * @return array
     */
    public function findOne($id) {
        return $this->crud->findByKey($id);
    }

    /**
     * delete row by id
     * 
     * @param string $id
     * @return int
     */
    public function deleteOne($id) {
        return $this->crud->delByKey($id);
    }

    public function delete($filter, $limit = 50, $start = 0) {
        return $this->crud->delete($filter, $limit, $start);
    }

    /**
     * get iterator of query result
     * 
     * @param array $filter
     * @param int $limit
     * @param int $start
     * @return Toknot\Share\DB\Table
     */
    public function find($filter, $limit, $start = 0) {
        return $this->table->iterator($filter, $limit, $start);
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
        return $this->crud->read($filter, $limit, $start);
    }

}

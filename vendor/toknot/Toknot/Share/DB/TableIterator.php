<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Toknot\Boot\Object;
use Toknot\Share\DB\DBA;

/**
 * TableIterator
 *
 * @author chopin
 */
abstract class TableIterator extends Object {

    abstract public function isCompositePrimaryKey();

    abstract public function select($where);

    abstract public function execute($limit, $offset);

    /**
     * create iterator from query result
     * 
     * @param string $where
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function iterator($where = null, $limit = 50, $offset = 0) {
        if ($this->iteratorArray) {
            $this->iteratorArray->closeCursor();
            $this->currentResult = [];
        }
        $this->select($where)->execute($limit, $offset);
        $this->iteratorArray = $this->statement;
        return $this;
    }

    public function current() {
        if ($this->key && !$this->isCompositePrimaryKey()) {
            $this->keyValue = $this->currentResult[$this->key];
        }
        return $this->currentResult;
    }

    public function rewind() {
        $this->fetchCursorIndex = 0;
        $this->currentResult = [];
    }

    public function key() {
        if ($this->key && !$this->isCompositePrimaryKey()) {
            return $this->keyValue;
        }
        return $this->fetchCursorIndex;
    }

    public function valid() {
        if (!$this->iteratorArray) {
            return false;
        }
        $this->currentResult = $this->iteratorArray->fetch(DBA::$fechStyle, DBA::$cursorOri, $this->fetchCursorIndex);
        return $this->currentResult;
    }

    public function next() {
        ++$this->fetchCursorIndex;
        return true;
    }

}

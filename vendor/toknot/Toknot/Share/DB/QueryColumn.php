<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

/**
 * Column
 *
 * @author chopin
 */
class QueryColumn {

    protected $columnName = '';
    protected $conn = '';
    /**
     *
     * @var QueryBulider
     */
    protected $qr = '';
    protected $sql = '';
    protected $subColumn = null;
    protected $table;

    public function __construct($columnName, $qr, Table $table) {
        $this->columnName = $columnName;
        $this->qr = $qr;
        self::$usedIndex++;
        $this->table = $table;
    }

    public function getSQL() {
        return $this->sql;
    }

    public function getColumnName() {
        return $this->columnName;
    }

    public function getAllColumnName() {
        return ($this->table->getTableAlias() ? $this->table->getTableAlias() : '') . '.' . $this->columnName;
    }

    public function leftConvert($value) {
        if ($value instanceof QueryColumn) {
            return $value->getAllColumnName();
        } else {
            return $this->qr->setParamter($this->getAllColumnName(), $value);
        }
    }

    public function set($value) {
        $left = $this->leftConvert($value);
        $this->qr->set($this->getAllColumnName(), $left);
        return $this;
    }

    public function eq($value) {
        $left = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " = $left";
        return $this;
    }

    public function in($values) {
        $this->sql = $this->getAllColumnName() . " IN (" . implode(',', $values) . ')';
        return $this;
    }

    public function notIn($values) {
        $this->sql = $this->getAllColumnName() . " NOT IN (" . implode(',', $values) . ')';
        return $this;
    }

    public function out($values) {
        $this->sql = $this->getAllColumnName() . " NOT IN (" . implode(',', $values) . ')';
        return $this;
    }

    public function isNull() {
        $this->sql = $this->getAllColumnName() . " IS NULL";
        return $this;
    }

    public function gt($value) {
        $left = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " > $left";
        return $this;
    }

    public function lt($value) {
        $left = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " < $left";
        return $this;
    }

    public function ge($value) {
        $left = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " >= $left";
        return $this;
    }

    public function le($value) {
        $left = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " <= $left";
        return $this;
    }

    public function ne($value) {
        $left = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " <> $left";
        return $this;
    }

    public function notNull() {
        $this->sql = $this->getAllColumnName() . ' IS NOT NULl';
        return $this;
    }

    public function has() {
        $this->sql = $this->getAllColumnName() . ' IS NOT NULl';
        return $this;
    }

    public function add($value) {
        $right = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " + $right";
        return $this;
    }

    public function mins($value) {
        $this->sql = $this->getAllColumnName() . ' - ' . $this->leftConvert($value);
        return $this;
    }

    public function mul($value) {
        $this->sql = $this->getAllColumnName() . ' * ' . $this->leftConvert($value);
        return $this;
    }

    public function div($value) {
        $this->sql = $this->getAllColumnName() . ' / ' . $this->leftConvert($value);
        return $this;
    }

    public function call($func, $values = []) {
        $args = '';
        if ($values) {
            $arg = [];
            foreach ($values as $v) {
                $arg[] = $this->leftConvert($v);
            }
            $args .= ', ' . implode(', 

         ', $arg);
        }
        $this->sql = "$func(" . $this->getAllColumnName() . " $args)";
        return $this;
    }

    public function operator($operator, $value) {
        $left = $this->leftConvert($value);
        $this->sql = $this->getAllColumnName() . " $operator $left";
        return $this;
    }

}

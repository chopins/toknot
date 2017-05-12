<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Toknot\Boot\ObjectAssistant;

/**
 * Column
 *
 * @author chopin
 */
class QueryColumn {
    use ObjectAssistant;

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

    /**
     *
     * @var string
     * @readonly
     */
    private $type = '';

    /**
     *
     * @var boolean
     * @readonly
     */
    private $isPk = false;

    /**
     *
     * @var boolean
     * @readonly 
     */
    private $isIndex = false;

    /**
     *
     * @var boolean
     * @readonly 
     */
    private $isUnique = false;

    /**
     *
     * @var int
     * @readonly
     */
    private $length = 0;

    public function __construct($columnName, QueryBulider $qr, Table $table) {
        $this->columnName = $columnName;
        $this->qr = $qr;
        $this->table = $table;
        $this->isPk = ($this->table->pk() == $this->columnName);
        $this->setCol();
    }

    protected function setCol() {
        $struct = $this->table->getTableStructure();
        $colsAttrs = $struct['column'][$this->columnName];
        $this->type = $colsAttrs['type'];
        $this->length = isset($colsAttrs['length']) ? $colsAttrs['length'] : 0;
        $this->checkIndex($struct['indexes']);
    }

    protected function checkIndex($indexes) {
        foreach ($indexes as $idxs) {
            if (!isset($idxs[$this->columnName])) {
                continue;
            }
            if ($idxs['type'] == 'index') {
                $this->isIndex = true;
                return;
            } elseif ($idxs['type'] == 'unique') {
                $this->isUnique = true;
                return;
            }
        }
    }

    public function getSQL() {
        return $this->sql;
    }

    public function getColumnName() {
        return $this->columnName;
    }

    public function getAllColumnName() {
        return ($this->table->getTableAlias() ? $this->table->getTableAlias() . '.' : '') . $this->columnName;
    }

    public function leftConvert($value) {
        if ($value instanceof QueryColumn) {
            return $value->getAllColumnName();
        } elseif ($value instanceof QueryWhere) {
            return $value;
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

    public function leftLike($value) {
        $this->sql = $this->getAllColumnName() . ' LIKE ' . $this->leftConvert('%' . $value);
        return $this;
    }

    public function rightLike($value) {
        $this->sql = $this->getAllColumnName() . ' LIKE ' . $this->leftConvert($value . '%');
        return $this;
    }

    public function like($value) {
        $this->sql = $this->getAllColumnName() . ' LIKE ' . $this->leftConvert('%' . $value . '%');
        return $this;
    }

    public function eqLike($value) {
        $this->sql = $this->getAllColumnName() . ' LIKE ' . $this->leftConvert($value);
        return $this;
    }
    
    public function notLeftLike($value) {
        $this->sql = $this->getAllColumnName() . ' NOT LIKE ' . $this->leftConvert('%' . $value);
        return $this;
    }

    public function notRightLike($value) {
        $this->sql = $this->getAllColumnName() . ' NOT LIKE ' . $this->leftConvert($value . '%');
        return $this;
    }

    public function notLike($value) {
        $this->sql = $this->getAllColumnName() . ' NOT LIKE ' . $this->leftConvert('%' . $value . '%');
        return $this;
    }

    public function notEqLike($value) {
        $this->sql = $this->getAllColumnName() . ' NOT LIKE ' . $this->leftConvert($value);
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

    public function __toString() {
        return $this->getSQL();
    }

}

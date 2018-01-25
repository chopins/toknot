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
use Toknot\Exception\BaseException;

/**
 * QueryWhere
 *
 * @author chopin
 */
class QueryWhere extends Object {

    protected $table = null;
    protected $qr = null;
    protected $andOrParts = [];
    protected $lastSQL;

    public function __construct(Table $table, QueryBulider $qr) {
        $this->table = $table;
        $this->qr = $qr;
    }

    public function andX() {
        $args = func_get_args();
        $this->lastSQL = $this->andOr($args, 'AND');
        return $this;
    }

    public function orX() {
        $args = func_get_args();
        $this->lastSQL = $this->andOr($args, 'OR');
        return $this;
    }

    protected function checkType($value) {
        if ($value instanceof QueryColumn) {
            return true;
        } elseif ($value instanceof QueryWhere) {
            return true;
        } else {
            return false;
        }
    }

    protected function expression($l, $r, $expr) {
        if ($this->checkType($l) && !$this->checkType($r)) {
            $hold = $this->qr->setParamter($l, $r);
            $this->lastSQL = "$l $expr $hold";
        } elseif ($this->checkType($r) && !$this->checkType($l)) {
            $hold = $this->qr->setParamter($r, $l);
            $this->lastSQL = "$hold $expr $l";
        } elseif ($this->checkType($l) && $this->checkType($r)) {
            $this->lastSQL = "$l $expr $r";
        } else {
            throw BaseException('at least one expr is be instance of QueryColumn or QueryWhere ');
        }
    }

    public function eq($l, $r) {
        $this->expression($l, $r, '=');
        return $this;
    }

    public function gt($l, $r) {
        $this->expression($l, $r, '>');
        return $this;
    }

    public function lt($l, $r) {
        $this->expression($l, $r, '<');
        return $this;
    }

    public function ge($l, $r) {
        $this->expression($l, $r, '>=');
        return $this;
    }

    public function le($l, $r) {
        $this->expression($l, $r, '<=');
        return $this;
    }

    public function ne($l, $r) {
        $this->expression($l, $r, '<>');
        return $this;
    }

    protected function andOr($args, $type) {
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $andList = array_map(function($expr) {
            if ($expr instanceof QueryColumn) {
                return $expr->getSQL();
            } elseif ($expr instanceof QueryWhere) {
                return $expr->getSQL();
            } else {
                throw new BaseException('AND OR expr must be instanceof QueryColumn or QueryWhere');
            }
        }, $args);

        return '(' . implode($type, $andList) . ')';
    }

    public function cols($cols) {
        return new QueryColumn($cols, $this->qr, $this->table);
    }

    public function __get($cols) {
        return $this->cols($cols, $this->qr, $this->table);
    }

    public function getSQL() {
        return $this->lastSQL;
    }

    public function __toString() {
        return $this->lastSQL;
    }

}

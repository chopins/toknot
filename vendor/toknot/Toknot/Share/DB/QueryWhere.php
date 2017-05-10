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
        return ($this->lastSQL = $this->andOr($args, 'AND'));
    }

    public function orX() {
        $args = func_get_args();
        return ($this->lastSQL = $this->andOr($args, 'OR'));
    }

    public function eq($l, $r) {
        return ($this->lastSQL = "$l = $r");
    }

    public function gt($l, $r) {
        return ($this->lastSQL = "$l > $r");
    }

    public function lt($l, $r) {
        return ($this->lastSQL = "$l < $r");
    }

    public function ge($l, $r) {
        return ($this->lastSQL = "$l >= $r");
    }

    public function le($l, $r) {
        return ($this->lastSQL = "$l <= $r");
    }

    public function ne($l, $r) {
        return "$l <> $r";
    }

    protected function andOr($args, $type) {
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $andList = array_map(function($expr) {
            if ($expr instanceof QueryColumn) {
                return $expr->getSQL();
            } else {
                return $expr;
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

    public function getWhereSQL() {
        return $this->lastSQL;
    }

}

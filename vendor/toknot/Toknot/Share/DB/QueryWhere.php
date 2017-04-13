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

    public function __construct(Table $table, QueryBulider $qr) {
        $this->table = $table;
        $this->qr = $qr;
    }

    public function andX() {
        $args = func_get_args();
        return $this->andOr($args, 'AND');
    }

    public function eq($k, $v) {
        $cols = new QueryColumn($k, $this->qr, $this->table);
        $cols->eq($v);
        return $cols->getSQL();
    }

    protected function andOr($args, $type) {
        if (count($args) == 1 && is_array($args[0])) {
            array_walk($args[0], function(&$v, $k) {
                $v = $this->eq($k, $v);
            });
            return '(' . implode($type, $args[0]) . ')';
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

    public function orX() {
        $args = func_get_args();
        return $this->andOr($args, 'OR');
    }

    public function cols($cols, $table) {
        return new QueryColumn($cols, $this->qr, $table);
    }

    public function __get($cols) {
        return $this->cols($cols, $this->qr, $this->table);
    }

}

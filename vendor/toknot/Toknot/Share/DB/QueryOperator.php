<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\DB;

use Toknot\Share\DB\QueryColumn;

/**
 * QueryExpression
 *
 * @author chopin
 */
class QueryOperator {

    protected $column = '';
    protected $columnName = '';

    public function __construct(QueryColumn $cols) {
        $this->column = $cols;
        $this->columnName = $this->getColumnName();
    }

    public function getColumnName() {
        return $this->column->getAllColumnName();
    }

    public function leftConvert($value) {
        return $this->column->leftConvert($value);
    }

    

}

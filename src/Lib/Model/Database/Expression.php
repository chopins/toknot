<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use Toknot\Boot\Kernel;
use Toknot\Lib\Model\Database\QueryExpression;

class Expression extends QueryExpression {

    private $operator = '';
    protected $left = '';
    protected $right = '';
    protected $table = null;
    protected $bindRight = false;
    protected $hasKey = false;
    protected $hasUnique = false;
    protected $hasIndex = false;
    protected $expType = false;

    const TYPE_LIKE_FUNC = 1;
    const TYPE_SET_VALUE = 2;

    public function __construct($operator, $expType = 0) {
        $this->operator = Kernel::SP . $operator . Kernel::SP;
        $this->expType = $expType;
    }

    public function left($v) {
        $this->left = $this->filterScalar($v);
        return $this;
    }

    public function right($v) {
        $this->right = $this->bindRight ? $v : $this->filterScalar($v);
        return $this;
    }

    public function bindRight() {
        $this->bindRight = true;
    }

    public function getOperator() {
        return $this->operator;
    }

    public function getExpression() {
        if (Kernel::isBitSet($this->expType, self::TYPE_LIKE_FUNC)) {
            $right = Kernel::LP . $this->right . Kernel::RP;
        } elseif (Kernel::isBitSet($this->expType, self::TYPE_SET_VALUE)) {
            return Kernel::SP . $this->left . $this->operator . $this->right . Kernel::SP;
        } else {
            $right = $this->right;
        }
        return Kernel::SP . Kernel::LP . $this->left . $this->operator . $right . Kernel::RP . Kernel::SP;
    }

    public function hasKey($set = null) {
        return $this->singleProperty('hasKey', $set);
    }

    public function hasUnique($set = null) {
        return $this->singleProperty('hasUnique', $set);
    }

    public function hasIndex($set = null) {
        return $this->singleProperty('hasIndex', $set);
    }

    protected function singleProperty($proper, $set = null) {
        if ($set === null) {
            return $this->$proper;
        }
        $this->$proper = $set;
        return $this->$proper;
    }

    protected function filterScalar($v) {
        if (!is_scalar($v)) {
            return $v;
        }
        return DB::instance()->quote($v);
    }

}

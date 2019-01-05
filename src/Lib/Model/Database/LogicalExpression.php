<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use Toknot\Lib\Model\Database\QueryExpression;
use Toknot\Boot\Kernel;

class LogicalExpression extends QueryExpression {

    private $args = [];
    private $operator = '';

    public function __construct($operator) {
        $this->operator = Kernel::SP . $operator . Kernel::SP;
    }

    public function arg($expression) {
        $this->args[] = $expression;
    }

    public function args(array $args) {
        $this->args = array_merge($this->args, $args);
    }

    public function push(...$args) {
        $this->args = array_merge($this->args, $args);
    }

    public function getExpression() {
        usort($this->args, array($this, 'sortCall'));
        return Kernel::SP . Kernel::LP . join($this->operator, $this->args) . Kernel::RP . Kernel::SP;
    }

    public function sortCall($a, $b) {
        if (($res = $this->indexTypeCall($a, $b, 'hasKey')) !== null) {
            return $res;
        } elseif (($res = $this->indexTypeCall($a, $b, 'hasUnique')) !== null) {
            return $res;
        } elseif (($res = $this->indexTypeCall($a, $b, 'hasIndex')) !== null) {
            return $res;
        }
        return 0;
    }

    public function indexTypeCall($a, $b, $func) {
        if ($a->$func() && $b->$func()) {
            return 0;
        } elseif ($a->$func()) {
            return 1;
        } elseif ($b->$func()) {
            return -1;
        }
        return null;
    }

}

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

use Toknot\Boot\Kernel;
use Toknot\Lib\Model\Database\DB;
use Toknot\Lib\Model\Database\QueryExpression;

class FunctionExpression extends QueryExpression {

    private $func = '';
    private $arg = [];

    public function __construct($name) {
        $this->func = $name;
    }

    public function arg($expression) {
        $this->arg[] = $this->filterScalar($expression);
        return $this;
    }

    public function args($args) {
        foreach ($args as $i => $arg) {
            $args[$i] = $this->filterScalar($arg);
        }
        $this->arg = array_merge($this->arg, $args);
        return $this;
    }

    public function push(...$args) {
        $this->args($args);
        return $this;
    }

    public function getExpression() {
        return Kernel::SP . $this->func . Kernel::LP . join(Kernel::COMMA, $this->arg) . Kernel::RP . Kernel::SP;
    }

    protected function filterScalar($v) {
        if (!is_scalar($v)) {
            return $v;
        }
        return DB::instance()->quote($v);
    }

}

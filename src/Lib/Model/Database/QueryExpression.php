<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\Model\Database;

abstract class QueryExpression {

    abstract function getExpression();

    public function __toString() {
        return $this->getExpression();
    }

}

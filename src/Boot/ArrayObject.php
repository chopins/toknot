<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Boot;

use ArrayObject as AO;
use Toknot\Boot\Kernel;

class ArrayObject extends AO {

    public function value($key) {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }
        return null;
    }

    public function __get($key) {
        return $this->value($key);
    }

    public function merge($param) {
        return Kernel::merge($this, $param);
    }

}

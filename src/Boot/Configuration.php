<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Boot;

use Toknot\Boot\ArrayObject;
use Toknot\Boot\Kernel;

class Configuration extends ArrayObject {

    public function offsetGet($key) {
        $key = Kernel::classToLower($key, Kernel::UDL);
        if (!$this->offsetExists($key)) {
            return null;
        }
        $value = parent::offsetGet($key);
        if (is_array($value)) {
            return new static($value);
        }
        return $value;
    }

    public function offsetExists($key) {
        $key = Kernel::classToLower($key, Kernel::UDL);
        return parent::offsetExists($key);
    }

    public function offsetSet($key, $value = null) {
        if (Kernel::getInstance()) {
            Kernel::getInstance()->runtimeException('can not set config at runtime');
        }
    }

    public function __isset($key) {
        $key = Kernel::classToLower($key, Kernel::UDL);
        return $this->offsetExists($key);
    }

}

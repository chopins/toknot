<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\Object;

class StandardClass extends Object {
    public $value = null;
    public function __construct($value = null) {
        $this->value = $value;
    }
    final public function setPropertie($name, $value) {
        $this->$name = new XStdClass($value);
    }
    public function __toString() {
        return $this->value;
    }

}
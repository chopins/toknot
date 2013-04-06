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

class ArrayElementObject  extends Object{
    /**
     * save element value
     *
     * @var mixed
     * @access public
     */
    public $value;
    public $name;

    public function __construct($value, $name = '') {
        $this->value = $value;
        $this->name = $name;
    }
    /**
     * set 
     * modify the storage value of specify key in XArrayObject
     * 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function set($value) {
        if(is_array($value)) {
            $this->name = new XArrayObject($value);
        } else {
            $this->name = $value;
        }
    }
    public function __unset($name) {
        if($name == 'name') unset($this);
    }
    public function __toString() {
        return $this->value;
    }
}
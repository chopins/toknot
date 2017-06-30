<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

/**
 * MethodHelper
 *
 */
class MethodHelper {

    private $prevObj = null;

    public function __construct($obj) {
        $this->prevObj = $obj;
    }

    protected function checkMethod($name) {
        $ref = new \ReflectionClass($this->prevObj);
        if (!$ref->hasMethod($name)) {
            $class = get_class($this->prevObj);
            throw new \BadMethodCallException("method $name not defined in class $class");
        }
    }

    public function __call($name, $arg = []) {
        $this->checkMethod($name);
        return array($this->prevObj, $name);
    }

    /**
     * 
     * @param string $name
     * @return string
     */
    public function __get($name) {
        $this->checkMethod($name);
        return $name;
    }

}

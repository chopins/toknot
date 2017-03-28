<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 * @filesource
 * @package Toknot.Boot
 */

namespace Toknot\Boot;

use Toknot\Boot\Object;

/**
 * Pipe
 *
 * @author chopin
 */
class Pipe extends Object {

    private $ret = null;

    /**
     * 
     * @param callable $func
     * @param array $arg
     */
    public function __construct($func, $arg) {
        $this->ret = self::callFunc($func, $arg);
        return $this;
    }

    public function result() {
        return $this->ret;
    }

    /**
     * 
     * @param callable $func
     */
    public function call($func) {
        $this->ret = self::callFunc($func, $this->ret);
        return $this;
    }

    /**
     * 
     * @param string $name
     * @param array $arg
     * @return $this
     */
    public function __call($name, $arg = []) {
        if (isset($arg[0])) {
            $class = $arg[0];
            $this->ret = $class->$name($this->ret);
        } else {
            $this->ret = $name();
        }
        return $this;
    }

    public function __invoke() {
        return $this->ret;
    }

}

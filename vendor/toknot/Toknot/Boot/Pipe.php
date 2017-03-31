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
    private $cxt = null;

    /**
     * 
     * @param callable $func
     * @param array $arg
     */
    public function __construct($cxt = null) {
        $this->cxt = $cxt;
    }

    public function result() {
        return $this->ret;
    }

    public function context($obj = null) {
        $this->cxt = $obj;
        return $this;
    }

    public function getContext() {
        return $this->cxt;
    }

    /**
     * 
     * @param callable $func
     */
    public function call($func, $arg = []) {
        if ($this->cxt) {
            $func = array($this->cxt, $func);
        }
        $this->ret = self::callFunc($func, array_merge($arg, $this->ret));
        return $this;
    }

    /**
     * 
     * @param string $name
     * @param array $arg
     * @return $this
     */
    public function __call($name, $arg = []) {
        return $this->call($name, $arg);
    }

    public function __invoke() {
        return $this->ret;
    }

}

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
use Toknot\Boot\Tookit;
use Toknot\Exception\BaseException;

/**
 * Decorator
 *
 * @author chopin
 */
class Decorator extends Object {

    protected $func = null;
    protected $decorators = [];
    protected $ret = null;
    protected $isClass = false;

    /**
     * construct a decorator
     * 
     * <code>
     * function deco1(func) {
     *      echo 'is deco1';
     *      func();
     *      return 1;
     * }
     * function deco2(func) {
     *    echo 'is deco2';
     * }
     * /*
     *  * @decorator deco2
     *  * @decorator deco1
     *  *\/
     * function call() {
     *     echo 'is call';
     * }
     * $decorator = new Decorator('call'); // is deco, is call, is deco2
     * $decorator();  //is call
     * </code>
     * 
     * @param callable $function
     */
    public function __construct($function, $isClass = false) {
        $this->func = $function;
        $this->ret = $function;

        $this->isClass = $isClass;

        if (is_object($function)) {
            $this->isClass = true;
        }

        $this->getDecorator();
        $this->call();
    }

    /**
     * 
     * @param array $param
     * @return mix
     */
    public function __invoke($param = []) {
        if (is_callable($this->ret) || $this->ret instanceof \Closure) {
            return self::callFunc($this->ret, $param);
        }

        throw new BaseException(gettype($this->ret) . ' is not callable');
    }

    protected function call() {
        foreach ($this->decorators as $call) {
            if (strpos($call, '::') !== false) {
                $calls = explode('::', Tookit::dotNS($call));
                $this->ret = self::invokeStatic($calls[0], $calls[1], [$this->ret]);
                continue;
            }
            $this->ret = self::callFunc($call, [$this->ret]);
        }
    }

    /**
     * 
     * @throws BaseException
     */
    protected function getDecorator() {
        if ($this->isClass) {
            $ref = new \ReflectionClass($this->func);
        } elseif (is_array($this->func)) {
            $ref = new \ReflectionMethod($this->func[0], $this->func[1]);
        } else {
            $ref = new \ReflectionFunction($this->func);
        }
        $doc = $ref->getDocComment();
        $m = preg_match_all('/^[\s]*\*[\s]*@decorator[\s]+([\w\.\->:]+)/m', $doc, $matches);
        if (!$m) {
            throw new BaseException("function $this->func not declare decorator");
        }
        if (!empty($matches[1])) {
            throw new BaseException("not found function $this->func of decorator");
        }
        $this->decorators = array_reverse($matches[0]);
    }

}

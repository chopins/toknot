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

/**
 * Promise
 *
 * @author chopin
 */
class Promise {

    private $promisePass = true;
    private $promiseReject = false;
    private $promiseContext = null;
    private $promiseExecCallable = '';
    private $promiseExecStat = true;

    /**
     * start new promise
     * 
     * the route map: promise --> then-->  -------- --> then ----------------> then
     *                               \---> otherwise ---/ -\----> otherwise ---/
     * @param callable $callable
     * @param array $argv
     * @return $this
     */
    public function __construct($passState = true, $elseState = false, $cxt = null) {
        $this->promiseExecCallable = null;
        $this->promiseExecStat = true;
        $this->promisePass = $passState;
        $this->promiseReject = $elseState;
        if (!is_null($cxt) && !is_object($cxt)) {
            throw new BaseException('promise context must give object');
        }
        $this->promiseContext = $cxt;
        return $this;
    }

    public function setPassState($state) {
        $this->promisePass = $state;
        return $this;
    }

    public function setReject($state) {
        $this->promiseReject = $state;
        return $this;
    }

    public function addContext($cxt) {
        $this->promiseContext = $cxt;
        return $this;
    }

    /**
     * repeat invoke previous callable
     * 
     * @param array $argv
     * @return $this
     * @throws BaseException
     */
    public function again($argv = []) {
        if (!$this->promiseExecCallable) {
            throw new BaseException('call function not give before call again()');
        }

        if ($this->promiseExecStat === self::PROMISE_PASS) {
            $this->promiseExecStat = self::callFunc($this->promiseExecCallable, $argv);
        }
        return $this;
    }

    /**
     * if previous return pass, call current callable
     * 
     * @param callable $callable
     * @param array $argv
     * @return $this
     */
    public function then($callable, $argv = []) {
        if ($this->promiseExecStat === $this->promisePass) {
            if ($this->promiseContext) {
                $callable = array($this->promiseContext, $callable);
            }
            $this->promiseExecCallable = $callable;
            $this->promiseExecStat = self::callFunc($callable, $argv);
        }
        return $this;
    }

    /**
     * if previous return reject, call current callable
     * 
     * @param callable $callable
     * @param array $argv
     * @return $this
     */
    public function otherwise($callable, $argv = []) {
        if ($this->promiseExecStat === $this->promiseReject) {
            if ($this->promiseContext) {
                $callable = array($this->promiseContext, $callable);
            }
            $this->promiseExecCallable = $callable;
            $this->promiseExecStat = self::callFunc($callable, $argv);
        }
        return $this;
    }

    public function getLastState() {
        return $this->promiseExecStat;
    }

}

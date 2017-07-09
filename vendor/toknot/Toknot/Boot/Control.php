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
class Control {

    use ObjectHelper;

    private $controlPass = true;
    private $controlReject = false;
    private $controlContext = null;
    private $controlExecCallable = '';
    private $controlExecStat = true;

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
        $this->controlExecCallable = null;
        $this->controlExecStat = true;
        $this->controlPass = $passState;
        $this->controlReject = $elseState;
        if (!is_null($cxt) && !is_object($cxt)) {
            throw new BaseException('control context must give object');
        }
        $this->controlContext = $cxt;
        return $this;
    }

    public function setPassState($state) {
        $this->controlPass = $state;
        return $this;
    }

    public function setReject($state) {
        $this->controlReject = $state;
        return $this;
    }

    public function addContext($cxt) {
        $this->controlContext = $cxt;
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
        if (!$this->controlExecCallable) {
            throw new BaseException('call function not give before call again()');
        }

        if ($this->controlExecStat === $this->controlPass) {
            $this->controlExecStat = self::callFunc($this->controlExecCallable, $argv);
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
        if ($this->controlExecStat === $this->controlPass) {
            if ($this->controlContext) {
                $callable = array($this->controlContext, $callable);
            }
            $this->controlExecCallable = $callable;
            $this->controlExecStat = self::callFunc($callable, $argv);
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
        if ($this->controlExecStat === $this->controlReject) {
            if ($this->controlContext) {
                $callable = array($this->controlContext, $callable);
            }
            $this->controlExecCallable = $callable;
            $this->controlExecStat = self::callFunc($callable, $argv);
        }
        return $this;
    }

    public function getLastState() {
        return $this->controlExecStat;
    }

}

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\Model;

use Toknot\Boot\Tookit;
use Toknot\Exception\BaseException;
use Toknot\Boot\Object;
use Toknot\Boot\Kernel;

/**
 * CheckValue
 *
 * @author chopin
 */
class CheckOption extends Object {

    protected $require = [];
    protected $option = [];
    protected $delimiter = ',';
    protected $depend = [];
    protected $group = [];
    protected $kernel = null;

    public function __construct($value = []) {
        $this->iteratorArray = $value;
        $this->kernel = Kernel::single();
    }

    /**
     * add require key of array
     * 
     * @param string $key
     * @return $this
     */
    public function addRequire($key) {
        $this->require[$key] = 1;
        return $this;
    }

    /**
     * add option key of array
     * 
     * @param string $key
     * @param string $default
     * @return $this
     */
    public function addOption($key, $default = '') {
        $this->option[$key] = $default;
        return $this;
    }

    /**
     * add a new depend key for specify key
     * 
     * @param string $depend    require depend key of array
     * @param string $key
     * @return $this
     */
    public function newDepend($depend, $key) {
        $this->depend[$key] = [$depend];
        return $this;
    }

    /**
     * add to a depend key for specify key
     * 
     * @param string $depend
     * @param string $key
     * @return $this
     */
    public function pushDepend($depend, $key) {
        if (isset($this->depend[$key])) {
            $this->depend[$key][] = $depend;
        } else {
            $this->depend[$key] = [$depend];
        }
        return $this;
    }

    /**
     * add a group, group item must has all or all not exists
     * 
     * @param string $item1
     * @param string $item2
     * @return $this
     */
    public function addGroup($item1, $item2) {
        $this->group[] = func_get_args();
        return $this;
    }

    /**
     * add multiple group 
     * 
     * @param array $goups
     */
    public function groups($goups) {
        $this->group = array_merge($this->group, $goups);
    }

    /**
     * add multiple requires
     * 
     * @param string|array $requires
     * @return $this
     * @throws BaseException
     */
    public function requires($requires) {
        if (is_string($requires)) {
            $keys = explode($this->delimiter, $requires);
            $requires = array_fill_keys($keys, 1);
        } elseif (!is_array($requires)) {
            throw new BaseException('CheckValue::requires() must give a array or string');
        }
        $this->require = array_merge($this->require, $requires);
        return $this;
    }

    /**
     * add multiple depend
     * 
     * @param array $arr
     * @return $this
     */
    public function depends($arr) {
        $this->depend = array_merge($this->depend, $arr);
        return $this;
    }

    /**
     * 
     * @return boolean
     */
    public function checkRequire() {
        foreach ($this->require as $k => $t) {
            if (!isset($this->iteratorArray[$k])) {
                return $k;
            }
        }
        return true;
    }

    /**
     * 
     * @return boolean
     */
    public function checkDepend() {
        foreach ($this->depend as $k => $t) {
            if (!isset($this->iteratorArray[$k])) {
                continue;
            }

            foreach ($t as $dk) {
                if (!isset($this->iteratorArray[$dk])) {
                    return [$k, $dk];
                }
            }
        }
        return true;
    }

    /**
     * 
     * @return boolean
     */
    public function checkGroup() {
        foreach ($this->group as $ks) {
            $unset = 0;
            foreach ($ks as $k) {
                if (!isset($this->iteratorArray[$k])) {
                    $unset++;
                }
            }
            if ($unset != 0 && $unset != count($ks)) {
                return $ks;
            }
        }
        return true;
    }

    public function checkOption() {
        foreach ($this->option as $k => $t) {
            Tookit::coalesce($this->iteratorArray, $k, $t);
        }
        return true;
    }

    public function checkAll($values = []) {
        if ($values) {
            $this->iteratorArray = $values;
        }
        return $this->kernel->promise()->addContext($this)
                ->then('checkRequire')
                ->then('checkDepend')
                ->then('checkGroup')
                ->then('checkOption')
                ->getLastState();
    }

}

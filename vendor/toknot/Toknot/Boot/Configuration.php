<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use Toknot\Boot\Object;

class Configuration extends Object {

    public function __construct($cfg) {
        $this->iteratorArray = $cfg;
    }

    public function __get($key) {
        $v = $this->iteratorArray[$key];

        if (is_array($v)) {
            return new static($v);
        } else {
            return $v;
        }
    }

    public function __set($name, $value) {
        $this->iteratorArray[$name] = $value;
    }

    public function __isset($name) {
        return array_key_exists($name, $this->iteratorArray);
    }

    public function __unset($name) {
        unset($this->iteratorArray[$name]);
    }

    public function offsetGet($offset) {
        $v = parent::offsetGet($offset);
        if (is_array($v)) {
            return new static($v);
        } else {
            return $v;
        }
    }

    public static function loadConfig($ini, $outphp) {
        $cfg = Tookit::readConf($ini, $outphp);
        return new static($cfg);
    }

    /**
     * not found return null else return the key value
     * 
     * <code>
     * $cfg->find('app.app_ns')
     * </code>
     * 
     * @param string $key
     * @return mixed
     */
    public function find($key) {
        $ks = explode('.', $key);
        $cur = $this->iteratorArray;
        foreach ($ks as $k) {
            if (array_key_exists($k, $cur)) {
                $cur = $cur[$k];
            } else {
                return null;
            }
        }
        return $cur;
    }

    public function toArray() {
        return $this->iteratorArray;
    }

}

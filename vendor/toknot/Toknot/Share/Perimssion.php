<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

class Perimssion {

    protected $nameList = [];
    protected static $perimssionList = [];
    protected $gmp = true;
    protected $bc = true;
    protected $supportMaxPer = 0;
    protected $noExt = false;
    protected static $instance = null;

    public function __construct($perlist, $mask = false) {
        if (extension_loaded('gmp') === false) {
            $this->gmp = false;
        } else if (extension_loaded('bcmath') == false) {
            $this->bc = false;
        }
        if ($mask) {
            self::$perimssionList = $perlist;
        } else {
            $this->extensionInfo();
            $this->nameList = $perlist;
        }
        self::$instance = $this;
    }

    /**
     * 
     * @param string $code
     * @return numeric
     */
    public static function getPerimssionMask($code) {
        if (isset(self::$perimssionList[$code])) {
            return self::$perimssionList[strtoupper($code)];
        }
        return 0;
    }

    /**
     * 
     * @param numeric $holdPerimssionMask
     * @param numeric $addPerimssionMask
     * @return numeric string
     */
    public static function addPerimssion($holdPerimssionMask, $addPerimssionMask) {
        return self::$instance->orOp($holdPerimssionMask, $addPerimssionMask);
    }

    /**
     * 
     * @param numeric $holdPerimssionMask
     * @param numeric $removePerimssionMask
     * @return numeric
     */
    public static function removePerimssion($holdPerimssionMask, $removePerimssionMask) {
        self::$instance->toHex($removePerimssionMask);
        return self::$instance->removebit($holdPerimssionMask, $removePerimssionMask);
    }

    /**
     * 
     * @param numeric $needPerimssionMask   hex ,dec, bin number string
     * @param numeric $holdPerimssionMask   hex,dec,bin number string
     * @return bool
     */
    public static function hasPerimssion($needPerimssionMask, $holdPerimssionMask) {
        return self::$instance->andOp($needPerimssionMask, $holdPerimssionMask) != 0;
    }

    /**
     * 
     * @return array
     */
    public static function getPerimssionAll() {
        return self::$perimssionList;
    }

    protected function makePerimssionAll() {
        foreach ($this->nameList as $k => $n) {
            self::$perimssionList[strtoupper($n)] = '0x' . dechex($this->pow(2, $k));
        }
    }

    protected function extensionInfo() {
        $nogmpbc = ($this->gmp === false && $this->bc === false);
        $this->supportMaxPer = (PHP_INT_SIZE * 8) - 2;
        if ($nogmpbc && count($this->nameList) > $this->supportMaxPer) {
            throw new \Exception("has not gmp and bcmath extension,only add {$this->supportMaxPer} perimssion");
        }
    }

    protected function pow($left, $right) {
        if ($this->gmp) {
            return gmp_pow($left, $right);
        } elseif ($this->bc) {
            return bcpow($left, $right);
        }
        return pow($left, $right);
    }

    protected function bitLen($left, $right) {
        $n1 = strlen($left);
        $n2 = strlen($right);
        return $n1 > $n2 ? $n1 : $n2;
    }

    protected function toHex(&$number) {
        if (strpos($number, '0') !== 0) {
            $number = dechex($number);
        }
    }

    protected function strBitOp($left, $right, $len, $callable) {
        $this->toHex($left);
        $this->toHex($right);
        $leftr = strrev($left);
        $rightr = strrev($right);
        $resr = '';
        for ($i = 2; $i < $len; $i++) {
            $resr .= $callable($leftr, $rightr, $i);
        }
        return '0x' . ltrim(strrev($resr), '0');
    }

    protected function andOp($left, $right) {
        if ($this->gmp) {
            return gmp_and($left, $right);
        } elseif ($this->bc) {
            $len = $this->bitLen($left, $right);
            return $this->strBitOp($left, $right, $len, function($l, $r, $i) {
                        return isset($l[$i]) && isset($r[$i]) && $l[$i] == $r[$i] && $l[$i] == '1' ? '1' : '0';
                    });
        }
        return $left & $right;
    }

    protected function orOp($left, $right) {
        if ($this->gmp) {
            return gmp_or($left, $right);
        } elseif ($this->bc) {
            $len = $this->bitLen($left, $right);
            return $this->strBitOp($left, $right, $len, function($l, $r, $i) {
                        return (isset($l[$i]) && $l[$i] == '1') || (isset($r[$i]) && $r[$i] == '1') ? '1' : '0';
                    });
        }
        return $left | $right;
    }

    protected function strSetBit($left, $index, $setOn = true) {
        $len = strlen($left);
        if ($index > $len && !$setOn) {
            return $left;
        }
        $len = $len > $index ? $len : $index;
        return $this->strBitOp($left, $index, $len, function($left, $index, $i) use($setOn) {
                    return $index != $i ? $left[$i] : ($setOn ? 1 : 0);
                });
    }

    protected function removebit($current, $remove) {
        if ($this->gmp) {
            $bitIndex = strlen($remove) - 2;
            return gmp_setbit($current, $bitIndex, false);
        } elseif ($this->bc) {
            $bitIndex = strlen($remove) - 2;
            return $this->strSetBit($current, $bitIndex, false);
        }
        return ~(~$current | $remove);
    }

}

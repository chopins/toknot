<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Http;

use Toknot\Di\ArrayObject;

class RequestElementValue extends ArrayObject {
    public function isEmail() {
        return is_email($this->value);
    }
    public function isNumber() {
        return is_numeric($this->value);
    }
    public function isWord() {
        return is_word($this->value);
    }
    public function isZhMoblie() {
        return is_moblie($this->value);
    }
    public function isString() {
        return is_string($this->value);
    }
    public function isInt() {
        return is_int($this->value);
    }
    public function noQuotes() {
        return str_replace(array("'",'"'),'',$this->value);
    }
}

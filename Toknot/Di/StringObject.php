<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\Object;
use \BadMethodCallException;
use \ReflectionExtension;
use \ArrayAccess;
use Toknot\Di\ArrayObject;
use Toknot\Exception\StandardException;

class StringObject extends Object implements ArrayAccess {

    protected $interatorArray = '';
    private $walkIndex = 0;
    private static $supportStringMethodList = array(
        0 => 'strptime',
        1 => 'wordwrap',
        2 => 'htmlspecialchars',
        3 => 'htmlentities',
        4 => 'html_entity_decode',
        5 => 'htmlspecialchars_decode',
        6 => 'sha1',
        7 => 'md5',
        8 => 'crc32',
        9 => 'strnatcmp',
        10 => 'strnatcasecmp',
        11 => 'strspn',
        12 => 'strcspn',
        13 => 'strtok',
        14 => 'strtoupper',
        15 => 'strtolower',
        16 => 'strpos',
        17 => 'stripos',
        18 => 'strrpos',
        19 => 'strripos',
        20 => 'strrev',
        21 => 'hebrev',
        22 => 'hebrevc',
        23 => 'nl2br',
        24 => 'stripslashes',
        25 => 'stripcslashes',
        26 => 'strstr',
        27 => 'stristr',
        28 => 'strrchr',
        29 => 'str_shuffle',
        30 => 'str_word_count',
        31 => 'str_split',
        32 => 'strpbrk',
        33 => 'strcoll',
        34 => 'substr',
        35 => 'substr_replace',
        36 => 'quotemeta',
        37 => 'ucfirst',
        38 => 'lcfirst',
        39 => 'ucwords',
        40 => 'strtr',
        41 => 'addslashes',
        42 => 'addcslashes',
        43 => 'rtrim',
        44 => 'str_replace',
        45 => 'str_ireplace',
        46 => 'str_repeat',
        47 => 'chunk_split',
        48 => 'trim',
        49 => 'ltrim',
        50 => 'strip_tags',
        51 => 'soundex',
        52 => 'str_getcsv',
        53 => 'str_pad',
        54 => 'chop',
        55 => 'strchr',
        56 => 'sscanf',
        57 => 'urlencode',
        58 => 'urldecode',
        59 => 'rawurlencode',
        60 => 'rawurldecode',
        61 => 'base64_decode',
        62 => 'base64_encode',
        63 => 'quoted_printable_decode',
        64 => 'quoted_printable_encode',
        65 => 'convert_cyr_string',
        66 => 'highlight_string',
        67 => 'strval',
        68 => 'crypt',
        69 => 'str_rot13',
    );

    public function __construct($string = '') {
        $this->interatorArray = $string;
        if (empty(self::$supportStringMethodList)) {
            self::$supportStringMethodList = self::supportStringMethod();
        }
    }

    public static function hasMethod($name) {
        return in_array($name, self::supportStringMethod());
    }

    public static function supportStringMethod() {
        $ref = new ReflectionExtension('standard');
        $functionList = $ref->getFunctions();
        $supprot = array();
        foreach ($functionList as $funcRef) {
            if ($funcRef->getNumberOfRequiredParameters() < 1) {
                continue;
            }
            $parameters = $funcRef->getParameters();

            if (strpos($funcRef->name, 'stream') === 0) {
                continue;
            }
            if (strpos($funcRef->name, 'str') !== 0 && $parameters[0]->name != 'str' && $parameters[0]->name != 'string') {
                continue;
            }
            if ($parameters[0]->isPassedByReference()) {
                continue;
            }
            $supprot[] = $funcRef->name;
        }
        return $supprot;
    }

    public function __call($stringFunction, $arguments) {
        if (!in_array($stringFunction, self::$supportStringMethodList))
            throw new BadMethodCallException("$stringFunction Method undefined in StringObject");

        array_unshift($arguments, $this->interatorArray);
        $str = call_user_func_array($stringFunction, $arguments);
        if (is_string($str)) {
            return new StringObject($str);
        } elseif (is_array($str)) {
            return new ArrayObject($str);
        } else {
            return $str;
        }
    }

    public static function rand($min, $max = 0) {
        if($min <1) {
            throw new StandardException("StringObject::rand() 1 parameter must greater 1, $min given");
        }
        //$word = str_repeat('1234567890qwertyuioplkjhgfdsazxcvbnmQWERTYUIOPLKHJGFDSAZXCVBNM',$max);
        $word = str_repeat('1234567890qwertyuioplkjhgfdsazxcvbnmQWERTYUIOPLKHJGFDSAZXCVBNM',5);
        $randStr = str_shuffle(str_shuffle($word));
        if ($max > 0) {
            $len = mt_rand($min, $max);
        } else {
            $len = $min;
        }
        return substr($randStr, 0, $len);
    }

    public function valueOf() {
        return $this->interatorArray;
    }

    public function __toString() {
        return $this->interatorArray;
    }

    public function count() {
        return strlen($this->interatorArray);
    }

    public function strlen() {
        return strlen($this->interatorArray);
    }

    public function rewind() {
        $this->walkIndex = 0;
    }

    public function current() {
        return $this->interatorArray[$this->walkIndex];
    }

    public function key() {
        return $this->walkIndex;
    }

    public function next() {
        $this->walkIndex++;
    }

    public function valid() {
        $key = $this->key();
        return isset($this->interatorArray[$key]);
    }

    public function offsetExists($offset) {
        return isset($this->interatorArray[$offset]);
    }

    public function offsetGet($offset) {
        return $this->interatorArray[$offset];
    }

    public function offsetSet($offset, $value) {
        if ($offset >= count($offset)) {
            $this->interatorArray .= $value;
        } else {
            $this->interatorArray = substr_replace($this->interatorArray, $value, $offset);
        }
    }

    public function offsetUnset($offset) {
        $this->interatorArray = substr_replace($this->interatorArray, '', $offset, 1);
    }
    
}
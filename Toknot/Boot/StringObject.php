<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use Toknot\Boot\Object;
use \BadMethodCallException;
use \ArrayAccess;
use Toknot\Boot\ArrayObject;
use Toknot\Exception\BaseException;

/**
 * String object
 */
class StringObject extends Object implements ArrayAccess {

    /**
     *
     * @var string
     * @access protected
     */
    protected $interatorArray = '';
    private $walkIndex = 0;
    const WORDS = '0987654321qwertyuiopasdfghjklmnbvcxzQWERTYUIUIOPLKJHGFDSAZXCVBNM';
    private static $supportStringMethodList = array(
         'strptime',
         'wordwrap',
         'htmlspecialchars',
         'htmlentities',
         'html_entity_decode',
         'htmlspecialchars_decode',
         'sha1',
         'md5',
         'crc32',
         'strnatcmp',
         'strnatcasecmp',
         'strspn',
         'strcspn',
         'strtoupper',
         'strtolower',
         'strpos',
         'stripos',
         'strrpos',
         'strripos',
         'strrev',
         'hebrev',
         'hebrevc',
         'nl2br',
         'stripslashes',
         'stripcslashes',
         'strstr',
         'stristr',
         'strrchr',
         'str_shuffle',
         'str_word_count',
         'str_split',
         'strpbrk',
         'strcoll',
         'substr',
         'substr_replace',
         'quotemeta',
         'ucfirst',
         'lcfirst',
         'ucwords',
         'strtr',
         'addslashes',
         'addcslashes',
         'rtrim',
         'str_replace',
         'str_ireplace',
         'str_repeat',
         'chunk_split',
         'trim',
         'ltrim',
         'strip_tags',
         'soundex',
         'str_getcsv',
         'str_pad',
         'chop',
         'strchr',
         'sscanf',
         'urlencode',
         'urldecode',
         'rawurlencode',
         'rawurldecode',
         'base64_decode',
         'base64_encode',
         'quoted_printable_decode',
         'quoted_printable_encode',
         'convert_cyr_string',
         'highlight_string',
         'strval',
         'crypt',
         'str_rot13',
    );

    public function __init($string = '') {
        $this->interatorArray = (string) $string;
    }

    /**
     * check the method whether be support by StringObject class
     * 
     * @param string $name
     * @return boolean
     */
    public static function supportMethod($name) {
        return in_array($name, self::supportStringMethod());
    }
    
    public static function getSupportMethod() {
        return self::$supportStringMethodList;
    }

    /**
     * Get StringObject support method
     * 
     * @return array
     */
    public static function supportStringMethod() {
        $functionList = get_extension_funcs('standard');
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

    public function __callMethod($stringFunction, $arguments) {
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

    /**
     * generate random string, if not given $max and greater then 0, will return 
     * fixed length random
     * string
     * 
     * @param integer $min The random string least length
     * @param integer $max The random string max length, default is 0
     * @param boolean $all Whether the random string contians all printable char
     * @return string
     * @throws BaseException
     */
    public static function rand($min, $max = 0, $all = false) {
        if ($min < 1) {
            throw new BaseException("StringObject::rand() 1 parameter must greater 1, $min given");
        }
        if ($max > 1) {
            $randlen = mt_rand($min, $max);
        } else {
            $randlen = $min;
        }
        $char = self::WORDS;
        if ($all) {
            $char .= '~`!@#$%^&*()_+-={}|[]\\:";\',./<>?';
        }
        return substr(str_shuffle($char),0,$randlen);
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

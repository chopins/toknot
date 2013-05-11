<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

abstract class Object implements \Iterator, \Countable {

    /**
     *
     * @var array
     * @access protected
     */
    protected $interatorArray = array();

    /**
     * propertieChange
     * whether object instance propertie change status
     *
     * @var bool
     * @access private
     */
    private $propertieChange = false;

    /**
     * instance
     * Object instance
     * 
     * @var object
     * @access private 
     */
    private static $instance = null;

    /**
     * singleton 
     * 
     * @param callable $funcname  options
     * @param mixed $params options, callable $funcname param or more
     * @static
     * @access public
     * @return object
     */
    final protected static function __singleton() {
        $className = get_called_class();
        if (is_object(self::$instance) && self::$instance instanceof $className) {
            return self::$instance;
        }
        self::$instance = new $className;
        $arg_num = func_num_args();
        if ($arg_num > 0) {
            $args = func_get_args();
            call_user_func_array($args[0], array_shift($args));
        }
        return self::$instance;
    }

    /**
     * __set 
     * set propertie value and save changed status
     * 
     * @param mixed $propertie 
     * @param mixed $value 
     * @final
     * @access public
     * @return void
     */
    final public function __set($propertie, $value) {
        $this->propertieChange = true;
        $this->setPropertie($propertie, $value);
    }

    /**
     * 
     * @param string $propertie
     * @param mixed $value
     * @access public
     * @return void 
     */
    protected function setPropertie($propertie, $value) {
        $this->$propertie = $value;
    }

    /**
     * isChange 
     * check class propertie whether change default value or set new propertie and it value;
     * 
     * @final
     * @access public
     * @return boolean
     */
    final public function isChange() {
        if ($this->propertieChange)
            return true;
        $ref = new ReflectionObject($this);
        $list = $ref->getDefaultProperties();
        $staticList = $ref->getStaticProperties();
        foreach ($list as $key => $value) {
            if (isset($staticList[$key])) {
                if (self::$$key != $value)
                    return true;
            } else {
                if ($this->$key != $value)
                    return true;
            }
        }
        return false;
    }

    public function rewind() {
        $ref = new ReflectionObject($this);
        $propertiesList = $ref->getProperties();
        $constantsList = $ref->getConstants();
        $this->interatorArray = array_merge($constantsList, $propertiesList);
        reset($this->interatorArray);
    }

    public function current() {
        return current($this->interatorArray);
    }

    public function key() {
        return key($this->interatorArray);
    }

    public function next() {
        next($this->interatorArray);
    }

    public function valid() {
        $key = $this->key();
        return isset($this->interatorArray[$key]);
    }
    public function count() {
        return count($this->interatorArray);
    }
    
}


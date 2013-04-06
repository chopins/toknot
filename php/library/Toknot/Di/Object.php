<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

abstract class Object implements \Iterator{
    
    private $interatorArray = array();

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
     * @param callable $funcname 
     * @param mixed $params 
     * @static
     * @access public
     * @return object
     */
    final protected static function __singleton($funcname = null, $params = null) {
        $class_name = get_called_class();
        if(self::$instance && self::$instance instanceof $class_name) {
            return self::$instance;
        }
        self::$instance = new $class_name;
        if($funcname) {
            if(!is_array($params)) {
                call_user_func($funcname,$params);
            } else {
                call_user_func_array($funcname,$params);
            }
        }
        return self::$instance;
    }

    /**
     * __set 
     * that changed propertie status
     * 
     * @param mixed $propertie 
     * @param mixed $value 
     * @final
     * @access public
     * @return void
     */
    final public function __set($propertie, $value) {
        $this->propertieChange = true;
        $this->__xset__($propertie,$value);
    }
    
    /**
     * 
     * @param type $propertie
     * @param type $value
     * @access public
     * @return void 
     */
    public function setPropertie($propertie, $value) {
        $this->$propertie = $value;
    }

    /**
     * isChange 
     * check class propertie whether change default value or set new propertie and it value;
     * 
     * @final
     * @access public
     * @return void
     */
    final public function isChange() {
        if($this->propertieChange) return true;
        $ref = new ReflectionObject($this);
        $list = $ref->getDefaultProperties();
        $staticList = $ref->getStaticProperties();
        foreach($list as $key=>$value) {
            if(isset($staticList[$key])) {
                if(self::$$key != $value) return true;
            } else {
                if($this->$key != $value) return true;
            }
        }
        return false;
    }
    
    public function rewind() {
        $ref = new ReflectionObject($this);
        $propertiesList = $ref->getProperties();
        $methodList = $ref->getMethods();
        $constantsList = $ref->getConstants();
        $this->interatorArray = array_merge($constantsList,$propertiesList,$methodList);
        reset($this->interatorArray);
    }
    
    public function current() {
        return current($this->interatorArray);
    }
    
    public function key() {
        return key($this->interatorArray);
    }
    
    public function next() {
        return next($this->interatorArray);
    }
    public function valid() {
        $key = $this->key();
        return isset($this->interatorArray[$key]);
    }
}


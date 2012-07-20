<?php
/**
 * Toknot
 *
 * XObject class, XArrayObject class, XArrayElementObject class, XStdClass class,
 *
 * PHP version 5.3
 * 
 * @package XDataStruct
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */
exists_frame();

/**
 * XObject 
 * base single depth object class of XPHPFramework
 * 
 * @package XDataStruct
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XObject {
    /**
     * whether object instance propertie change status
     *
     * @var bool
     * @access private
     */
    private $propertieChange = false;

    /**
     * final __set function that changed propertie status
     */
    final public function __set($propertie, $value) {
        $this->propertieChange = true;
        $this->__xset__($propertie,$value);
    }
    public function __xset__($propertie, $value) {
        $this->$propertie = $value;
    }
    /**
     * check class propertie whether change default value or set new propertie and it value;
     */
    final public function isChange() {
        if($this->propertieChange) return true;
        $ref = new ReflectionObject($this);
        $list = $ref->getDefaultProperties();
        foreach($list as $key=>$value) {
            if($this->$key != $value) return true;
        }
        return false;
    }
    public function __toString() {
        return $this->value;
    }
}

/**
 * XArrayElementObject 
 * XArrayObject element object class
 * 
 * @uses XObject
 * @package XDataStruct
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XArrayElementObject  extends XObject{
    /**
     * save element value
     *
     * @var mixed
     * @access public
     */
    public $value;
    public $name;

    public function __construct($value, $name = '') {
        $this->value = $value;
        $this->name = $name;
    }
    public function __xset__($name,$value) {
        if(is_array($value)) {
            $this->$name = new XArrayObject($value);
        } else if(is_object($value) || is_resource($value)) {
            $this->$name = $value;
        } else {
            $this->$name = new XArrayElementObject($value);
        }
    }
}

class XStdClass extends XObject {
    public $value = null;
    public function __construct($value = null) {
        $this->value = $value;
    }
    final public function __xset__($name, $value) {
        $this->$name = new XStdClass($value);
    }
}


/**
 * This class allows arrays to work as object;
 */
class XArrayObject implements ArrayAccess,Countable {
    protected $storage = null;
    protected $elementObjectName = 'XArrayElementObject';
    protected $storageType = 'ArrayObject';
    protected $indexType = 'hex';
    public function __construct($array) {
        if($array instanceof $this->storageType) {
            $this->storage = $array;
            return;
        } else {
            $this->storage = $this->getStorageObject();
        }
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $key = $this->getIndex($key);
                $this->storage->offsetSet($key,new XArrayObject($value));
            } else {
                $this->setKeyValue($key,$value);
            }
        }
    }
    public function getStorageObject() {
        $ref = new ReflectionClass($this->storageType);
        return $ref->newInstance();
    }
    public function setElement($value, $sKey = '') {
        $ref = new ReflectionClass($this->elementObjectName);
        return $ref->newInstance($value,$sKey);
    }
    public function getIndex($str_key) {
        $int_hash = 0;
        $key_len = strlen($str_key);
        for($i=0;$i<$key_len;$i++) {
            $int_hash = ((($int_hash <<5) + $int_hash) + ord($str_key[$i])) % 5813;
        }
        return $this->indexType == 'hex'? dechex($int_hash) : $int_hash;
    }
    public function offsetExists($str_index) {
        $index = $this->getIndex($str_index);
        return $this->storage->offsetExists($index);
    }
    public function offsetGet($str_index) {
        $index = $this->getIndex($str_index);
        return $this->storage->offsetGet($index);
    }
    public function count() {
        return $this->storage->count();
    }
    public function offsetUnset($sKey) {
        $this->stroage->offsetUnset($int_key);
    }
    public function setKeyValue($sKey,$value) {
        $key = $this->getIndex($sKey);
        if(is_array($value)) {
            $this->storage->offsetSet($key,new XArrayObject($value));
        } else {
            $this->storage->offsetSet($key, $this->setElement($value,$sKey));
        }
    }
    public function offsetSet($sKey, $value) {
        $this->setKeyValue($sKey,$value);
    }
    public function __set($sKey, $value) {
        $this->offsetSet($sKey,$value);
    }
    public function __get($sKey) {
        if($this->offsetExists($sKey)) {
            return $this->offsetGet($sKey);
        }
    }
    public function __isset($sKey) {
        return $this->offsetExists($sKey);
    }
    public function __unset($sKey) {
        $this->offsetUnset($sKey);
    }

}


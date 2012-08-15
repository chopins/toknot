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
        $static_list = $ref->getStaticProperties();
        foreach($list as $key=>$value) {
            if(isset($static_list[$key])) {
                if(self::$$key != $value) return true;
            } else {
                if($this->$key != $value) return true;
            }
        }
        return false;
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
    /**
     * set 
     * modify the storage value of specify key in XArrayObject
     * 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function set($value) {
        if(is_array($value)) {
            $this->name = new XArrayObject($value);
        } else {
            $this->name = $value;
        }
    }
    public function __unset($name) {
        if($name == 'name') unset($this);
    }
    public function __toString() {
        return $this->value;
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
    public function __toString() {
        return $this->value;
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
    public function isArray($key) {
        if($this->offsetExists($key)) {
            $keyValue = $this->offsetGet($key);
            if(is_array($keyValue)) return true;
            return $keyValue instanceof XArrayObject;
        }
        return false;
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

/**
 * XTemplateObject 
 * the XTemplate class $T properties of proto
 * 
 * @uses XObject
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XTemplateObject extends XObject {
    /**
     * name 
     * the template file name
     * 
     * @var mixed
     * @access public
     */
    public $name = null;

    /**
     * type 
     * the template filetype
     * 
     * @var mixed
     * @access public
     */
    public $type = 'htm';

    /**
     * data_cache 
     * only cache view data
     * 
     * @var mixed
     * @access public
     */
    public $data_cache = false;

    /**
     * cache_time 
     * the cache data or file expires seconds if open cache, and default 300 seconds
     * 
     * @var float
     * @access public
     */
    public $cache_time = 300;

    /**
     * static_cache 
     * save view-class output html to file if be set true
     * 
     * @var mixed
     * @access public
     */
    public $static_cache = false;

    /**
     * TPL_INI 
     * configuration for tpl
     * 
     * @var mixed
     * @access public
     */
    public $TPL_INI;
    private $cache_dir;
    public $be_cache = false;
    public function __construct($TPL_INI, $cache_dir) {
        $this->TPL_INI = $TPL_INI;
        $this->cache_dir = $cache_dir;
    }
    public function check_cache() {
        $ins = XTemplate::singleton($this->TPL_INI);
        $ins->set_cache_dir($this->cache_dir);
        $this->be_cache = $ins->get_cache($this);
    }
}

/**
 * XDBConf 
 * the Database config object proto
 * 
 * @uses XObject
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XDBConf extends XObject {

    /**
     * dbtype 
     * set database type
     * 
     * @var mixed
     * @access public
     */
    public $dbtype = null;

    /**
     * dbhost 
     * set database connect host or open path
     * 
     * @var mixed
     * @access public
     */
    public $dbhost = null;

    /**
     * dbuser 
     * the database username
     * 
     * @var mixed
     * @access public
     */
    public $dbuser = null;

    /**
     * dbpass 
     * the database password of user
     * 
     * @var mixed
     * @access public
     */
    public $dbpass = null;


    /**
     * dbport 
     * if connect by network, set the connect port
     * 
     * @var mixed
     * @access public
     */
    public $dbport = null;
    public $pconnect = false;
}

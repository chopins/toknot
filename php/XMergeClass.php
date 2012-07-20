<?php
/**
 * Toknot
 *
 * XMergeCLass
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
 * XMergeCLass 
 * 
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XMergeCLass {
    private $add_func = null;
    private $propertie = null;
    private $class_id = null;
    private $ref_list = null;
    public $class_list = array();
    private $file_last_mtime = 0;
    private $merge_instance = null;
    public $cfg;
    public function __construct($cfg) {
        $this->cfg = $cfg;
        $this->add_func = new SplObjectStorage();
        $this->propertie = new SplObjectStorage();
        $this->ref_list = new SplObjectStorage();
    }
    public function get_defined_expression($fs,$ref) {
        if(!is_object($ref)) throw new XException('Only support reflection function or method instance');
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();
        $funcname = $ref->getName();
        $res = '';
        if($fs->line > $start) {
            rewind($fs->f);
            $fs->line = 0;
        }
        while(!feof($fs->f)) {
            $fs->line++;
            $buff = fgets($fs->f);
            if($fs->line >= $start) {
                $no_comment = explode('//',trim($buff));
                $res .= $no_comment[0]."\n";
            }
            if($fs->line >= $end) break;
        }
        $part_arr = explode('function ',$res);
        foreach($part_arr as $v) {
            if(strpos($v,$funcname) !== false) {
                break;
            }
        }
        $v = ($ref->isProtected() ? 'protected function ':'public function ').$v."\n";
        return $v;
    }
    private function get_member(ReflectionClass $refObj,$filter_private=true) {
        if($filter_private) {
            $methodList = $refObj->getMethods(ReflectionMethod::IS_PUBLIC ^ ReflectionMethod::IS_PROTECTED);
        } else {
            $methodList = $refObj->getMethods();
        }
        if($filter_private) {
            $propertieList = $refObj->getProperties(ReflectionProperty::IS_PUBLIC ^ ReflectionProperty::IS_PROTECTED);
        } else {
            $propertieList = $refObj->getProperties();
        }
        $defaultPropretieList = $refObj->getDefaultProperties();
        foreach($propertieList as $p) {
            if($p->isPrivate()) continue;
            $pro = new stdClass;
            $pro->key = $p->getName();
            $pro->value = var_export($defaultPropretieList[$pro->key],true);
            $pro->isProtected = $p->isProtected();
            $this->propertie->attach($pro);
        }
        $fs = new stdClass;
        $file_name = $refObj->getfilename();
        $classname = $refObj->getName();
        $fs->f = fopen($file_name, 'r',false);
        $fs->line = 0;
        if(!$fs->f) throw new XException("Can not open file $file_name");
        foreach($methodList as $m) {
            $mc = new stdClass;
            $mc->expression = $this->get_defined_expression($fs,$m);
            $mc->parameters = $m->getParameters();
            $mc->classname = $classname;
            $mc->filename = $file_name;
            $mc->line = $m->getStartLine();
            $mc->protected = $m->isProtected();
            $mc->method_name = $m->getName();
            $this->add_func->attach($mc);
            $this->class_list[] = $classname;
        }
    }
    private function cache_file() {
        return __X_APP_DATA_DIR__."/{$this->cfg->data_php_cache}/{$this->class_id}.php";
    }
    public function merge_class() {
        if(!$this->class_id) throw new XException('muset before add class'); 
        if(file_exists($this->cache_file())) {
            $create_cache_time = filemtime($this->cache_file());
            if($this->file_last_mtime <=$create_cache_time) {
                include_once($this->cache_file());
                return new $this->class_id;
            }
        }
        foreach($this->ref_list as $lobj) {
            $this->get_member($lobj->ref_obj,$lobj->fprivate);
        }
        $class_expression = "<?php\nexists_frame();\nfinal class {$this->class_id}{\n";
        foreach($this->propertie as $p) {
            $access_control = $p->isProtected ? 'protected':'public';
            $class_expression .= "$access_control \${$p->key}={$p->value};\n";
        }
        $class_list = array_keys(get_object_vars($this->ref_list));
        $class_expression .= 'public $_xclass_merge_class_info = array("'.implode('","',$class_list).'");';
        foreach($this->add_func as $value) {
            $class_expression .= $value->expression;
        }
        $class_expression .= '}';
        file_put_contents($this->cache_file(),$class_expression);;
        include_once($this->cache_file());
        return new $this->class_id;
    }
    public function add_class(ReflectionClass $refObj, $filter_private = true) {
        if($refObj instanceof ReflectionClass) {
            $file_name = $refObj->getfilename();
            $classname = $refObj->getName();
            if($this->class_id == null) $this->class_id = 'merge';
            $this->class_id .= "_$classname";
            $this->class_id .= '_'.md5($file_name);
            $st = new stdClass;
            $st->ref_obj = $refObj;
            $st->fprivate = $filter_private;
            $st->classname = $classname;
            $this->ref_list->attach($st);
            if(filemtime($file_name) > $this->file_last_mtime) {
                $this->file_last_mtime = filemtime($file_name);
            }
            return;
        }
        throw new XException('Only add class of ReflectionClass instance');
    }
    public function __destruct() {
    }
}

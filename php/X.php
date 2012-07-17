<?php
/**
 * XPHPFramework
 *
 * X class
 *
 * PHP version 5.3
 * 
 * @category phpframework
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 2.2
 */
exists_frame();
/**
 * Base class for user classes, Provides web request and runtime data storage structure
 * and database administration class instance, template class instance
 * 
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 */
abstract class X {
    /**
     * Model Object instance handler storage
     *
     * @var array
     * @access public-readonly
     */
    private $dbm = array();

    /**
     * libtemplate class instance
     *
     * @var object
     * @access public-readonly
     */
    private $tpl_instance = null;

    /**
     * configuration object storage
     *
     * @var object
     * @access public-readonly
     */
    private $_CFG = null;

    /**
     * clean all object instance flag
     *
     * @var bool
     * @access public-readonly
     */
    private $free_instance = false;

    /**
     * user class call time
     *
     * @var int
     * @access public-readonly
     */
    private $visit_time = 0;

    /**
     * user access ip
     *
     * @var string
     * @access public-readonly
     */
    private $visit_ip = 0;

    /**
     * data object storage of end-user request
     *
     * @var object
     * @access public-readonly
     */
    private $R = null;

    /**
     * template data object storage of user class
     *
     * @var object
     * @access public-readonly
     */
    private $D = null;

    private $AjaxData = null;

    /**
     * user class set tpl file data object storage to template class
     *
     * @var object
     * @access public-readonly
     */
    private $T = null;

    /**
     * save javascript of user application
     *
     * @var string
     * @access public-readonly
     */
    private $_x_js = '';

    /**
     * check X of call_init method be called status
     *
     * @var bool
     * @access public-readonly
     */
    private $initStat = false;

    private $display_html = '';
    public $stop_run = false;
    private $headers = array();
    /**
     * construct base data that main init X class properties value
     * and common tpl data
     *
     * @access protected
     */
    final public function call_init() {
        $this->_CFG = $GLOBALS['_CFG'];
        $this->display_html = '';
        $this->visit_time = empty($_SERVER['REQUEST_TIME']) ? time() : $_SERVER['REQUEST_TIME'];
        $this->visit_ip = get_uip();
        $this->R = new XRequest($this->_CFG->ajax_key, $this->_CFG->ajax_flag);
        $this->D = new XStdClass();
        $this->T = new XObject();
        $this->init_var_dirname();
        $this->initStat = true;
    }

    /**
     * if request method is OPTIONS, the method will be call , and respones URI support HTTP method list 
     * 
     * @param string $method_name 
     * @final
     * @access public
     * @return void
     */
    final public function getOptions($method_name) {
        $request_method_list = array('G'=>'GET','P'=>'POST','U'=>'PUT','D'=>'DELETE','T'=>'TRACE','H'=>'HEAD');
        $support_list = array();
        foreach($request_method_list as $prefix => $method) {
            if($this->__isset($prefix.$method_name)) {
                $support_list[] = $method;
            }
        }
        return $this->exit_json(1,$_SERVER['REQUEST_URI'],$support_list);
    }

    /**
     * set HTTP header of item
     * 
     * @param mixed $header 
     * @final
     * @access protected
     * @return void
     */
    final protected function xheader($header) {
        if(PHP_SAPI == 'cli') {
            $header = trim($header);
            if(in_array($header,$this->headers)) return;
            $this->headers[] = $header;
        } else {
            header($header);
        }
    }

    /**
     * do call model class method
     *
     * @param string $model_name  call model class name, could use directory
     * @return Object  the model class instance
     */
    final protected function LM($model_name, $method_name) {
        if(isset($this->dbm[$model_name])) return $this->dbm[$model_name];
        $model_file = __X_APP_ROOT__."/{$this->_CFG->php_model_dir_name}/$model_name.php";
        $model_class = basename($model_name);
        if(!class_exists($model_class,false)) {
            if(!file_exists($model_file)) throw new XException("$file not exists");
            check_syntax($model_file);
            include_once($model_file);
        }
        $model_ref = new ReflectionClass($model_class);
        $this->dbm[$model_name] = $model_ref->newInstance();
        return $this->dbm[$model_name];
    }

    /**
     * call other view class or it is method
     * 
     * @param string $view  the view class name , 
     *                      it has 'G','P' .. etc that is  prefix character of request method
     * @param string $func 
     * @final
     * @access protected
     * @return void
     */
    final protected function CV($view, $method_name = null) {
        $view_class = basename($view);
        if(!class_exists($view_class, false)) {
            $file = __X_APP_ROOT__. "/{$this->_CFG->php_dir_name}/{$view}.php";
            if(!file_exists($file)) throw new XException("$file not exists");
            check_syntax($file);
            include_once $file;
        }
        $view_ref = new ReflectionClass($view_class);
        $ins = $view_ref->newInstance();
        $ins->call_init();
        if($method_name == null) return $ins;
        return call_user_func(array($ins,$method_name));
    }
    /**
     * user array save your site config by key/value storage to file
     *
     * @param array $runtime_config  your site configuration
     */
    final protected function save_all_runtime_config(array $runtime_config) {
        $file_config = $this->load_runtime_config();
        $runtime_config = array_merge($file_config,$runtime_config);
        file_put_contents(__X_APP_DATA_DIR__."/{$this->_CFG->runtime_config}", serialize($runtime_config));
    }
    
    /**
     * load your site configuration data
     *
     * @return array  return all configuration
     */
    final protected function load_runtime_config() {
        if(!file_exists(__X_APP_DATA_DIR__."/{$this->_CFG->runtime_config}")) {
            $runtime_config = array();
            file_put_contents(__X_APP_DATA_DIR__."/{$this->_CFG->runtime_config}", serialize($runtime_config));
            return $runtime_config;
        }
        $runtime_config = file_get_contents(__X_APP_DATA_DIR__."/{$this->_CFG->runtime_config}");
        return unserialize($runtime_config);
    }

    /**
     * save one configuration by key and value
     *
     * @param string $key  key name of one config
     * @param string $value  the one config value
     */
    final protected function save_runtime_config($key, $value) {
        $runtime_config = $this->load_runtime_config();
        $runtime_config[$key] = $value;
        file_put_contents(__X_APP_DATA_DIR__."/{$this->_CFG->runtime_config}", serialize($runtime_config));
    }

    /**
     * call user class method and instantiated libtemplate
     *
     * @param string $method_name   user class method name
     */
    final public function run($method_name) {
        if($this->stop_run) return;
        $re = call_user_func(array($this,$method_name));
        $this->init_tpl();
    }

    /**
     * auto set site common tpl data
     */
    final private function construct_standard_template_data() {
        $this->D->R = $this->R;
        foreach($this->_CFG->tpl->common_tpldata as $key => $value) {
            $this->D->$key = $value;
        }
    }

    /**
     * instantiated database operation class
     */
    final private function initdb() {
        $this->db_instance = new dba();
    }

    /**
     * instantiated libtemplate class and parse tpl file and output html
     */
    final private function init_tpl() {
        if($this->T->isChange() == false) {
            return;
        }
        $this->construct_standard_template_data();
        $this->tpl_instance = new XTemplate($this->_CFG,$this->T,$this->D);
        $this->tpl_instance->fetch_templete();
        if(is_object($this->tpl_instance)) {
            $this->tpl_instance->display();
        }
    }
    final public function get_json_tpl() {
        return $this->tpl_instance->get_json_tpl();
    }
    final public function get_display_html() {
        if(is_object($this->tpl_instance)) {
            return $this->tpl_instance->get_html();
        } else {
            return  null;
        }
    }
    /**
     * get error log by one day that logs save your data directory, the log contains php error
     * and exception info
     *
     * @param string $date  date of get log one day
     * @return array  return one array, one element is a error trace_item
     */
    final public function get_error_log($date = '') {
        if(empty($date)) {
            $date = date('Ymd');
        }
        $log_file = __X_APP_DATA_DIR__."/{$this->_CFG->error_log_dir}/$date";
        if(is_file($log_file)) {
            $log_str = file_get_contents($log_file);
            $log_array = explode($this->_CFG->exception_seg_line,$log_str);
            return $log_array;
        } else {
            return array();
        }
        return $log_str;
    }

    /**
     * check file mime type, and save the file mime type to 
     *
     * @param string $file  needle check file name that contains the file absolute path
     * @return string   the file mime type
     */
    public function check_file_type($file) {
        if(function_exists('finfo_open') && function_exists('finfo_file')) {
            $fif = finfo_open(FILEINFO_MIME_TYPE);
            $file_mime = finfo_file($fif,$file);
        } else {
            $file_mime = @mime_content_type($file);
        }
        return $file_mime;
    }
    
    /**
     * exit script and print ajax format text
     *
     * @param int $status   this response result status code
     * @param string $message   this response result description
     * @param array $data   this response return result data
     */
    final public function exit_json($status, $message, $data = null) {
        $return_data= array();
        $return_data['status'] = $status;
        $return_data['message'] = $message;
        $return_data['data'] = $data;
        $json_encode = json_encode($return_data);
        $this->xexit($json_encode);
    }
    
    /**
     * exit script and print XML document text
     * 
     * @param mixed $status 
     * @param mixed $message 
     * @param mixed $data 
     * @access public
     * @return void
     */
    final public function exit_xml($status, $message, $data = null) {
        $xml  = '<?xml version="1.0" encoding="'.$this->_CFG->encoding.'"?>';
        $xml .= '<root>';
        $xml .= "<status>{$status}</status>";
        $xml .= "<message>{$message}</message>";
        if(!is_array($data)) {
            $xml .= "<data>{$data}</data>";
        } else {
            $xml .= '<data>' . $this->array2xml($data).'</data>';
        }
        $xml .= '</root>';
        $this->xexit($xml);
    }

    /**
     * convert array to XML document 
     * 
     * @param array $array 
     * @access public
     * @return void
     */
    final public function array2xml(array $array) {
        $xml = '';
        foreach($data as $key=>$value) {
            if(is_array($value)) {
                $xml .= $this->array2xml($value);
            } else {
                $xml .= "<{$key}>$value</{$key}>";
            }
        }
        return $xml;
    }
    /**
     * exit script and print javascript of one variables defined by array
     *
     * @param string $var_name  the variables name
     * @param array $array   the variables value that is  array
     */
    final public function exit_js_array($var_name,$array) {
        $json = json_encode($array);
        $js = "var $var_name=$json;";
        $this->xexit($js);
    }

    /**
     * defined javascript variables
     *
     * @param string $var_name  the javascript variables name
     * @param mixed $value   the javascript variables value
     */
    final public function set_js_var($var_name,$value) {
        if(is_resource($value)) throw new XException('can not set resource to javascript of variables');
        if(is_object($value) || is_array($value)) {
            $value = json_encode($value);
        }
        $this->_x_js .= "var {$var_name}={$value};";
    }
    
    /**
     * user application exec exit operation instend exit() of php
     */
     final public function xexit($str = null) {
         if(PHP_CLI) {
            echo $str;
            return $this->__destruct();
         } else {
             die($str);
         }
     }

    /**
     * get all defined javascript variables and return javascript within HTML text
     *
     * @return string   HTML text format of javascript text
     */
    final public function get_js() {
        $re = "<script type=\"text/javascript\">{$this->_x_js}</script>";
        $this->_x_js = '';
        return $re;
    }

    final private function init_var_dirname() {
        if($this->_CFG->check_data_dir === false) return;
        $cache_dir = __X_APP_DATA_DIR__."/{$this->_CFG->data_cache}"; 
        $conf_dir = __X_APP_DATA_DIR__.'/conf';
        if(is_file(__X_APP_DATA_DIR__)) throw new XException(__X_APP_DATA_DIR__ .'is not directory');
        else if(!file_exists(__X_APP_DATA_DIR__)) mkdir(__X_APP_DATA_DIR__);
        else if(!is_writable(__X_APP_DATA_DIR__)) throw new XException(__X_APP_DATA_DIR__ .'can not write');
        if(is_file($cache_dir)) throw new XException("$cache_dir is not directory");
        else if(!file_exists($cache_dir)) mkdir($cache_dir);
        else if(!is_writable($cache_dir)) throw new XException("$cache_dir can not write");
        if(is_file($conf_dir)) throw new XException("$conf_dir is not directory");
        else if(!file_exists($conf_dir)) mkdir($conf_dir);
        else if(!is_writable($conf_dir)) throw new XException("$conf_dir can not write");
    }
    final public function __get($name) {
        if(isset($this->$name)) {
            return $this->$name;
        }
        return $this->__xget__($name);
    }
    final public function __isset($name) {
        return isset($this->$name);
    }
    final public function __set($name, $value) {
        if(isset($this->$name)) {
            throw new XException('Can not allow set X class properties');
        }
        $this->__xset__($name,$value);
    }
    public function __xget__($name) {}
    public function __xset__($name,$value) {}
    public function __destruct() {
        $this->tpl_instance = null;
        unset($this);
    }
}

<?php
/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;

abstract class Loader extends Object{
    /**
     * Model Object instance handler storage
     *
     * @var array
     * @access public-readonly
     */
    private $model_list = array();

    private $view_instance_list = array();
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
    final public static function singleton() {
        return parent::__singleton();
    }
    /**
     * construct base data that main init X class properties value
     * and common tpl data
     *
     * @access protected
     */
    final public function call_init() {
        $class_name = get_class($this);
        $this->view_instance_list[$class_name] = true;
        $this->_CFG = XConfig::CFG();
        $this->display_html = '';
        $this->visit_time = empty($_SERVER['REQUEST_TIME']) ? time() : $_SERVER['REQUEST_TIME'];
        $this->visit_ip = get_uip();
        $this->R = new XRequest($this->_CFG);
        $this->D = new XStdClass();
        $this->T = new XTemplateObject($this->_CFG->tpl, __X_APP_DATA_DIR__.'/'.$this->_CFG->app->data_cache);
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
    final public function get_options($view_class,$method_name) {
        $request_method_list = array('G'=>'GET','P'=>'POST','U'=>'PUT','D'=>'DELETE','T'=>'TRACE','H'=>'HEAD');
        $support_list = array();
        $method_name = substr($method_name,1);
        foreach($request_method_list as $prefix => $method) {
            $name = $prefix.$method_name;
            if(method_exists($view_class,$name)) {
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
    final protected function xHeader($header) {
        if(PHP_SAPI == 'cli') {
            $header = trim($header);
            if(in_array($header,$this->headers)) return;
            $this->headers[] = $header;
        } else {
            header($header);
        }
    }

    /**
     * do call model class
     *
     * @param string $model_name  call model class name, could use directory
     * @return Object  the model class instance
     */
    final protected function LM($model_name) {
        $model_name = ltrim($model_name,'/');
        if(!isset($this->model_list[$model_name])) {
            $model_file = __X_APP_ROOT__."/{$this->_CFG->php_model_dir_name}/$model_name.php";
            $model_class = basename($model_name);
            $model_class = $model_class.'Model';
            if(!class_exists($model_class,false)) {
                if(!file_exists($model_file)) throw new XException("$model_file not exists");
                check_syntax($model_file);
                include_once($model_file);
            }
            if(!class_exists($model_class,false)) {
                throw new XException("$model_name not found");
            }
        }
        $model_ins = $model_class :: singleton();
        $this->model_list[$model_name] = $model_ins;
        return $model_ins;
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
    final protected function CV($view) {
        $view_class = basename($view);
        $view = ltrim($view,'/');
        if(!class_exists($view_class, false)) {
            $file = __X_APP_ROOT__. "/{$this->_CFG->php_dir_name}/{$view}.php";
            if(!file_exists($file)) throw new XException("$file not exists");
            check_syntax($file);
            include_once $file;
        }
        $ins = $view_class :: singleton();
        $ins->call_init();
        $this->view_instance_list[$view_class] = $ins;
        return $ins;
    }

    /**
     * user array save your site config by key/value storage to file
     *
     * @param array $runtime_config  your site configuration
     */
    final protected function saveAllRuntimeConfig(array $runtime_config) {
        $file_config = $this->load_runtime_config();
        $runtime_config = array_merge($file_config,$runtime_config);
        file_put_contents(__X_APP_DATA_DIR__."/{$this->_CFG->runtime_config}", serialize($runtime_config));
    }
    
    /**
     * load your site configuration data
     *
     * @return array  return all configuration
     */
    final protected function loadRuntimeConfig() {
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
    final protected function saveRuntimeConfig($key, $value) {
        $runtime_config = $this->load_runtime_config();
        $runtime_config->$key = $value;
        file_put_contents(__X_APP_DATA_DIR__."/{$this->_CFG->runtime_config}", serialize($runtime_config));
    }

    /**
     * call user class method and instantiated libtemplate
     *
     * @param string $method_name   user class method name
     */
    final public function run($method_name) {
        if($this->stop_run) return;
        $this->$method_name();
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
     * instantiated libtemplate class and parse tpl file and output html
     */
    final private function init_tpl() {
        if($this->T->name === null) {
            return;
        }
        $this->tpl_instance = XTemplate :: singleton($this->_CFG->tpl);
        if($this->T->be_cache) {
            return;
        }
        $this->construct_standard_template_data();
        $this->tpl_instance->set_cache_dir(__X_APP_DATA_DIR__.'/'.$this->_CFG->app->data_cache);
        $this->tpl_instance->execute($this->T, $this->D);
    }
    final public function getJSONTpl() {
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
    final public function getErrorLog($date = '') {
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
    final public function exitJSON($status, $message, $data = null) {
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
    final public function exitXML($status, $message, $data = null) {
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
    final public function array2XML(array $data) {
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
    final public function exitJSArray($var_name,$array) {
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
    final public function setJSVar($var_name,$value) {
        if(is_resource($value)) throw new XException('can not set resource to javascript of variables');
        if(is_object($value) || is_array($value)) {
            $value = json_encode($value);
        }
        $this->_x_js .= "var {$var_name}={$value};";
    }
    
    /**
     * user application exec exit operation instend exit() of php
     */
     final public function xExit($str = null) {
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
    final public function getJS() {
        $re = "<script type=\"text/javascript\">{$this->_x_js}</script>";
        $this->_x_js = '';
        return $re;
    }

    final private function init_var_dirname() {
        if($this->_CFG->app->check_data_dir === false) return;
        $cache_dir = __X_APP_DATA_DIR__."/{$this->_CFG->app->data_cache}"; 
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
    public function __xget__($name);
    public function __xset__($name,$value);
    final public function __destruct() {
        $this->tpl_instance = null;
        unset($this);
    }
}

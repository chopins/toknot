<?php
/**
 * Toknot
 *
 * XScheduler class
 *
 * PHP version 5.3
 * 
 * @category php
 * @package Base
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 0.6
 */
exists_frame();

/**
 * XScheduler 
 * 
 * @uses XObject
 * @final
 * @package Base
 * @version $id$
 * @copyright 2012 The Author
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 */

final class XScheduler {
    public $app_instance;
    private $app_method;
    private $exception_string = '';
    private $server = null;
    private $utf8 = 'utf8';
    private $encodeing = '';
    private $_CFG;
    public function __construct() {
        if(version_compare(PHP_VERSION,'5.3.0') < 0) {
            throw new XException('XPHPFramework need run in php varsion lagre 5.3.0, your php version is'.PHP_VERSION);
        }
        define('PHP_CLI',PHP_SAPI =='cli');
        if(PHP_CLI == false) define('__X_WEB_ROOT__',dirname($_SERVER['SCRIPT_FILENAME']));
        defined('__X_APP_ROOT__') || define('__X_APP_ROOT__',__X_WEB_ROOT__);
        $_ENV['__X_OUT_BROWSER__']     = false;
        $_ENV['__X_EXCEPTION_THROW__'] = false;
        $_ENV['__X_FATAL_EXCEPTION__'] = false;
        $xconfig = XConfig::singleton();
        $this->_CFG = $xconfig->get_cfg_arr();
        $this->encoding = $this->_CFG['encoding'];
        $_ENV['__X_CALL_PAGE_DIR__']   = __X_APP_ROOT__.'/'.$this->_CFG['php_dir_name'];
        $this->check_superglobals();
        $this->set_time_zone();
        if(PHP_CLI && __X_NO_WEB_SERVER__ === false) {
            //fclose(STDERR);
            return new XWebServer($this);
        } else if(PHP_CLI && array_search('-d',$_SERVER['argv']) !== false) {
            return $this->call_loop();
        } else {
            ini_get('register_globals') and new XException('Need close php register_globals in php.ini');
            $this->load_app();
            echo $this->get_html();
        }
    }

    public function call_loop() {
        $_ENV['__X_CALL_PAGE_FILE__'] = __X_APP_ROOT__.'/'. $this->_CFG['php_dir_name'].__X_DAEMON_LOOP_FILE__;
        if(!file_exists($_ENV['__X_CALL_PAGE_FILE__'])) {
            throw new XException("File {$_ENV['__X_CALL_PAGE_FILE__']} not be found");
        }
        daemon();
        include_once($_ENV['__X_CALL_PAGE_FILE__']);
        exit(0);
    }
    /**
     * Load user application view class
     * 
     * @access public
     * @return void
     */
    public function load_app() {
        $this->exception_string = null;
        $this->init_env_var();
        try {
            $this->load_application_class_file();
        } catch(XException $e) {
            $this->exception_string = $e->getXDebugTraceAsString();
            gc_collect_cycles();
            return;
        }
    }
    /**
     * check php superglobals whether be set or 
     * not will set $_SERVER,$_GET,$_COOKIE,$_POST,$_FILES
     * 
     * @access private
     * @return void
     */
    private function check_superglobals() {
        $variables_order = strtoupper(ini_get('variables_order'));
        if(PHP_CLI == false && strpos($variables_order,'P') === false) {
            $this->import_post();
        } else if(strpos($variables_order,'P') === true && $this->utf8 != $this->encoding) {
            $_POST = unserialize(mb_convert_encoding(serialize($_POST), $this->utf8, $this->encoding));
        }
        if(strpos($variables_order,'S') === false) {
            $_SERVER['_'] = getenv('_');
            if(PHP_CLI == false) {
                $_SERVER['REQUEST_URI']     = getenv('REQUEST_URI');
                $_SERVER['SCRIPT_FILENAME'] = getenv('SCRIPT_FILENAME');
                $_SERVER['DOCUMENT_URI']    = getenv('DOCUMENT_URI');
                $_SERVER['REQUEST_METHOD']  = getenv('REQUEST_METHOD');
                $_SERVER['PATH_INFO']       = getenv('PATH_INFO');
                $_SERVER['SERVER_ADDR']     = getenv('SERVER_ADDR');
                $_SERVER['HTTP_HOST']       = getenv('HTTP_HOST');
                $_SERVER['SERVER_NAME']     = getenv('SERVER_NAME');
                $_SERVER['QUERY_STRING']    = getenv('QUERY_STRING');
            }
        }
        if(PHP_CLI == false && strpos($variables_order,'G') === false) {
            if($this->encoding != $this->utf8) {
                $_SERVER['QUERY_STRING'] = mb_convert_encoding($_SERVER['QUERY_STRING'], $this->utf8,$this->encoding);
            }
            parse_str($_SERVER['QUERY_STRING'],$_GET);
        } else if(strpos($variables_order,'G') === true && $this->utf8 != $this->encoding) {
            $_GET = unserialize(mb_convert_encoding(serialize($_GET), $this->utf8, $this->encoding));
        }

        if(PHP_CLI == false && strpos($variables_order,'C') === false) {
            if($this->encoding != $this->utf8) {
                $_SERVER['HTTP_COOKIE'] = empty($_SERVER['HTTP_COOKIE']) ? getenv('HTTP_COOKIE') : $_SERVER['HTTP_COOKIE'];
                $_SERVER['HTTP_COOKIE'] = mb_convert_encoding($_SERVER['HTTP_COOKIE'], $this->utf8, $this->encoding);
            }
            get_cookie();
        } else if(strpos($variables_order,'C') === true && $this->utf8 != $this->encoding) {
            $_COOKIE = unserialize(mb_convert_encoding(serialize($_COOKIE), $this->utf8, $this->encoding));
        }


    }
    /**
     * set $_POST adn $_FILES superglobals
     * 
     * @access private
     * @return void
     */
    private function import_post() {
        $http_body = file_get_contents('php://input','r');
        if(!empty($http_body)) {
            $content_type = getenv('HTTP_CONTENT_TYPE');
            if($content_type == 'application/x-www-form-urlencoded') {
                if($this->encoding != $this->utf8) 
                    $http_body = mb_convert_encoding($http_body, $this->utf8,$this->encoding);
                parse_str($http_body,$_POST);
            } else {
                $content_len = getenv('HTTP_CONTENT_LENGTH');
                $c_field = trim(strtok($content_type,';'));
                $upload_max_filesize = conv_human_byte(ini_get('upload_max_filesize'));
                while($c_field !== false) {
                    switch($c_field) {
                    case 'multipart/form-data':
                        $c_field = trim(strtok('='));
                    break;
                    case 'boundary':
                        if(($c_field = strtok(';')) === false) {
                            $boundary = '--'.trim($c_field);
                        } else {
                            $lt = explode('=',$content_type);
                            $boundary = '--'. trim(array_pop($lt));
                        }
                        $c_field = strtok(';');
                    break;
                    default:
                        $c_field = strtok('=');
                    break;
                    }
                }
                if(empty($boundary)) return;
                $part_arr = explode($boundary,$http_body);
                $body_end = false;
                foreach($part_arr as $part) {
                    if(empty($part)) continue;
                    if(trim($part) == '--') {
                        $body_end = true;
                        break;
                    }
                    $content_arr = explode("\r\n\r\n",$part,2);
                    $content_data = rtrim($content_arr[1]);
                    $content_field = trim(strtolower(strtok($content_arr[0],':')));
                    while(false !== $content_field) {
                        switch($content_field) {
                            case 'content-disposition':
                                $content_field = strtok(';');
                                $content_field = trim(strtok('='));
                            break;
                            case 'name':
                                $name = strtok('"');
                                if($name == 'MAX_FILE_SIZE') $form_max_size = $content_data;
                                $content_field = trim(ltrim(strtok('='),';'));
                                if($content_field === false) $content_field = trim(strtok(':'));
                                else $content_field = trim($content_field);
                            break;
                            case 'filename':
                                $filename = strtok('"');
                                $content_field = strtok(':');
                                if($content_field === false) $content_field = trim(strtok(':'));
                                else $content_field = strtolower(trim($content_field));
                            break;
                            case 'content-type':
                                $file_type = trim(strtok("\r\n"));
                                $content_field = strtok(':');
                            break;
                            default:
                                $content_field = strtok(';');
                                $content_field = strtok('=');
                            break;
                        }
                    }
                    if(isset($name) && isset($filename) && $filename !== false) {
                        $upfile_tmp_dir = isset($this->cfg->server->upfile_tmp_dir) ? 
                                            $this->cfg->server->upfile_tmp_dir:sys_get_temp_dir();
                        $tmp = tempnam($upfile_tmp_dir,'tmp_XPF_');
                        $file_len = strlen($content_data);
                        if($file_len > $upload_max_filesize) {
                            $errno = UPLOAD_ERR_INI_SIZE;
                        } else if($file_len == 0) {
                            $errno = UPLOAD_ERR_NO_FILE;
                        } else if(isset($form_max_size) && $form_max_size < $file_len) {
                            $errno = UPLOAD_ERR_FORM_SIZE;
                        } else if(empty($upfile_tmp_dir) || !is_dir($upfile_tmp_dir)) {
                            $errno = UPLOAD_ERR_NO_TMP_DIR;
                        } else if($body_end == false) {
                            $errno = UPLOAD_ERR_PARTIAL;
                        } else {
                            $errno = UPLOAD_ERR_OK;
                        }
                        if($errno == UPLOAD_ERR_OK) {
                            $fp = file_put_contents($tmp,$content_data);
                            if($fp === false) $errno = UPLOAD_ERR_CANT_WRITE;
                        }
                        if(substr($name,-1,2) == '[]') {
                            $_FILES[$name]['name'][] = $filename;
                            $_FILES[$name]['type'][] = $file_type;
                            $_FILES[$name]['size'][] = $file_len;
                            $_FILES[$name]['tmp_name'][] = $tmp;
                            $_FILES[$name]['error'][] = $errno;
                        } else {
                            $_FILES[$name]['name'] = $filename;
                            $_FILES[$name]['type'] = $file_type;
                            $_FILES[$name]['size'] = $file_len;
                            $_FILES[$name]['tmp_name'] = $tmp;
                            $_FILES[$name]['error'] = $errno;
                        }
                    } elseif(isset($name)) {
                        if($this->encoding != $this->utf8) {
                            $name = mb_convert_encoding($name, $this->utf8,$this->encoding);
                            $content_data = mb_convert_encoding($content_data, $this->utf8,$this->encoding);
                        }
                        if(substr($name,-1,2) == '[]') {
                            $_POST[$name][] = $content_data;
                        } else {
                            $_POST[$name] = $content_data;
                        } 
                    }
                }
            }
        }
    }
    /**
     * initialize environment variables of frameworker 
     * 
     * @access private
     * @return void
     */
    private function init_env_var() {
        $uri = strtolower($_SERVER['REQUEST_URI']);
        if(($pos = strpos($uri,'?')) !== false) {
            $uri_path     = substr($uri,0,$pos);
            $query_string = substr($uri,$pos+1);
        } else {
            $uri_path     = $uri;
        }
        $call_page_func = $call_page_name = $prefix_path = '';
        $url_file_suffix = '.'.$this->_CFG['url_file_suffix'];
        if($this->_CFG['uri_mode'] == 1 && isset($query_string)) {
            $_GET['a']      = empty($_GET['a']) ? '':$_GET['a'];
            $uri_path       = dirname($_GET['a']);
            $call_page_func = basename($_GET['a']);
        } else if($this->_CFG['uri_mode'] == 2) { //PATH_INFO mode
            if(empty($_SERVER['PATH_INFO'])) {
                $_SERVER['PATH_INFO'] = str_replace('/'.basename($_SERVER['SCRIPT_FILENAME']),'',$_SERVER['PHP_SELF']);
            }
            $uri_path = dirname($_SERVER['PATH_INFO']);
            $call_page_func = basename($_SERVER['PATH_INFO']);
            if(empty($_SERVER['PATH_INFO'])) $call_page_func = basename($_SERVER['DOCUMENT_URI'], $uri_file_suffix);
        } else if($this->_CFG['uri_mode'] == 4) {
        } else {
            if($uri_path == '/') {
                $_SERVER['DOCUMENT_URI'] = strtok($this->_CFG['web_index'],':');
                $call_page_func = basename($_SERVER['DOCUMENT_URI'],$url_file_suffix);
                $call_page_name = $call_page_func;
            } else if(dirname($uri_path) == '/') {
                $call_page_name = basename($uri_path);
                $call_page_func = basename(strtok($htis->_CFG['web.index'],' '), $url_file_suffix);
            } else {
                $call_page_func = basename($uri_path, $url_file_suffix);
                $call_page_name = basename(dirname($uri_path));
                $prefix_path    = dirname(dirname($uri_path));
                if($prefix_path == '/') $prefix_path = '';
            }
        }
        $add_sub_domain_path = '';
        if($this->_CFG['subsite_mode'] > 0) {
            if($this->_CFG['subsite_mode'] < $this->_CFG['subsite_start_level']) {
                throw new XException('subsite_start_level not be greater than subsite_mode in your confingure file');
            }
            if($_SERVER['SERVER_ADDR'] != $_SERVER['HTTP_HOST']) {
                if(empty($_SERVER['HTTP_HOST'])) {
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
                }
                if(!isip($_SERVER['HTTP_HOST'])) {
                   $sub_domain_list        = explode('.',$_SERVER['HTTP_HOST']);
                    $sub_domain_list       = array_reverse($sub_domain_list);
                    $sub_domain_list_count = count($sub_domain_list) -1;
                    if($sub_domain_list_count >= $this->_CFG['subsite_start_level']) {
                        for($i=$this->_CFG['subsite_start_level'];$i<=$this->_CFG['subsite_mode'];$i++) {
                            $add_sub_domain_path = "{$sub_domain_list[$i]}/{$add_sub_domain_path}";
                        }
                    }
                }
            }
        }
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $request_method = 'G';
            break;
            case 'POST':
                $request_method = 'P';
            break;
            case 'PUT':
                $request_method = 'U';
            break;
            case 'HEAD':
                $request_method = 'H';
            break;
            case 'TRACE':
                $request_method = 'T';
            break;
            case 'DELETE':
                $request_method = 'D';
            break;
            default :
                $request_method = 'O';
            break;
        }

        $_ENV['__X_CALL_PAGE_NAME__']    = $call_page_name;
        $_ENV['__X_CALL_PAGE_FILE__']    = "{$_ENV['__X_CALL_PAGE_DIR__']}{$add_sub_domain_path}{$prefix_path}/{$call_page_name}.php";
        $_ENV['__X_CALL_PAGE_FUNC__']    = $request_method.$call_page_func;
        $_ENV['__X_APP_UI_DIR__']        = __X_APP_ROOT__.'/'.$this->_CFG['ui_dir_name'];
        $_ENV['__X_APP_PHP_ERROR_LOG__'] = __X_APP_DATA_DIR__.'/'.$this->_CFG['error_log_dir'].'/'.date('Ymd');
    }

    /**
     * load view class of user's application
     * 
     * @access private
     * @return void
     */
    private function load_application_class_file() {
        if(!file_exists($_ENV['__X_CALL_PAGE_FILE__'])) {
            throw new XException("File {$_ENV['__X_CALL_PAGE_FILE__']} not be found");
        }
        check_syntax($_ENV['__X_CALL_PAGE_FILE__']);
        if(!class_exists($_ENV['__X_CALL_PAGE_NAME__'], false)) {
            include($_ENV['__X_CALL_PAGE_FILE__']);
        }
        if(!class_exists($_ENV['__X_CALL_PAGE_NAME__'],false)) {
            throw new XException("Class {$_ENV['__X_CALL_PAGE_NAME__']} not be found");
        }
        $classname = $_ENV['__X_CALL_PAGE_NAME__'];
        $method = $_ENV['__X_CALL_PAGE_FUNC__'];
        $ref = new ReflectionClass($_ENV['__X_CALL_PAGE_NAME__']);
        $request_method = substr($_ENV['__X_CALL_PAGE_FUNC__'],0,1);
        if($request_method == 'O') {
            $this->app_instance = $ref->newInstance();
            $this->app_instance->run('getOptions',substr($_ENV['__X_CALL_PAGE_FUNC__'],1));
            return;
        }
        $call_method = $ref->hasMethod($_ENV['__X_CALL_PAGE_FUNC__']);
        if($call_method === false) {
            throw new XException("Class {$_ENV['__X_CALL_PAGE_NAME__']} method {$_ENV['__X_CALL_PAGE_FUNC__']} not be found");
        }
        $call_method = $ref->getMethod($_ENV['__X_CALL_PAGE_FUNC__']);
        if($call_method->isDestructor()) {
            throw new XException('Can not call destruct method');
        }
        if($call_method->isPrivate() || $call_method->isProtected()) {
            throw new XException("Class {$_ENV['__X_CALL_PAGE_NAME__']} method {$_ENV['__X_CALL_PAGE_FUNC__']} is private or protected");
        }
        $refX = $ref->getParentClass();
        if($refX === false || $refX->getName() != 'X') {
            throw new XException("Class $classname need extends XPHPFramework of class X");
        }
        $this->app_instance = $ref->newInstance();
        if($call_method->isConstructor() && $this->app_instance->initStat === false) {
            throw new XException("because class {$_ENV['__X_CALL_PAGE_FUNC__']} defined constructor , so need call to \$this->call_init() within method {$_ENV['__X_CALL_PAGE_FUNC__']} is required in file {$_ENV['__X_CALL_PAGE_FILE__']}");
        }
        $this->app_instance->call_init();
        if($ref->hasMethod('init')) {
            $this->app_instance->init();
        }
        $this->app_instance->run($_ENV['__X_CALL_PAGE_FUNC__']);
    }

    /**
     * get html string of view class of user's application
     * 
     * @access public
     * @return void
     */
    public function get_html() {
        if($_ENV['__X_EXCEPTION_THROW__'] && $_ENV['__X_FATAL_EXCEPTION__']) {
            $html = $this->exception_string;
        } else {
            $html = $this->app_instance->get_display_html();
            $this->app_instance = null;
            if(PHP_CLI) gc_collect_cycles();
        }
        if($this->encoding != $this->utf8) {
            $html = mb_convert_encoding($html, $this->encoding, $this->utf8);
        }
        return $html;
    }
    /**
     * set application timezone of the application
     * 
     * @access public
     * @return void
     */
    public function set_time_zone() {
        if(empty($this->_CFG['timezone'])) {
            throw new XException('Application timezone unset in config file ');
        }
        date_default_timezone_set($this->_CFG['timezone']);
    }
}

<?php
/**
 * XPHPFramework
 *
 * XScheduler class
 *
 * PHP version 5.3
 * 
 * @category phpframework
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release 2.3
 */
exists_frame();
/**
 * XPHPFramework scheduler
 * 
 * @package XPHPFramework
 * @author chopins xiao <chopins.xiao@gmail.com>
 */
//调度器
final class XScheduler extends XObject {
    public $app_instance;
    private $app_method;
    private $cfg;
    private $exception_string = '';
    private $server = null;
    public function __construct() {
        if(version_compare(PHP_VERSION,'5.3.0') < 0) {
            throw new XException('XPHPFramework need run in php varsion lagre 5.3.0, your php version is'.PHP_VERSION);
        }
        define('PHP_CLI',PHP_SAPI =='cli');
        $_ENV['__X_OUT_BROWSER__'] = false;
        $_ENV['__X_EXCEPTION_THROW__'] = false;
        $_ENV['__X_FATAL_EXCEPTION__'] = false;
        $this->load_cfg();
        $this->check_superglobals();
        $this->set_time_zone();
        if(PHP_CLI) {
            //fclose(STDERR);
            return new XWebServer($this);
        } else {
            ini_get('register_globals') and new XException('Need close php register_globals in php.ini');
            $this->load_app();
            echo $this->get_html();
        }
    }
    public function load_app() {
        $this->exception_string = null;
        $this->define_env_constanst();
        try {
            $this->load_application_class_file();
        } catch(XException $e) {
            $this->exception_string = $e->getXDebugTraceAsString();
            gc_collect_cycles();
            return;
        }
    }
    private function check_superglobals() {
        $variables_order = strtoupper(ini_get('variables_order'));
        if(PHP_CLI == false && strpos($variables_order,'P') === false) {
            $this->import_post();
        }
        if(strpos($variables_order,'S') === false) {
            $_SERVER['_'] = getenv('_');
            if(PHP_CLI == false) {
                $_SERVER['REQUEST_URI'] = getenv('REQUEST_URI');
                $_SERVER['SCRIPT_FILENAME'] = getenv('SCRIPT_FILENAME');
                $_SERVER['DOCUMENT_URI'] = getenv('DOCUMENT_URI');
                $_SERVER['REQUEST_METHOD'] = getenv('REQUEST_METHOD');
                $_SERVER['PATH_INFO'] = getenv('PATH_INFO');
                $_SERVER['SERVER_ADDR'] = getenv('SERVER_ADDR');
                $_SERVER['HTTP_HOST'] = getenv('HTTP_HOST');
                $_SERVER['SERVER_NAME'] = getenv('SERVER_NAME');
                $_SERVER['QUERY_STRING'] = getenv('QUERY_STRING');
            }
        }
        if(PHP_CLI == false && strpos($variables_order,'G') === false) {
            parse_str($_SERVER['QUERY_STRING'],$_GET);
        }
        if(PHP_CLI == false && strpos($variables_order,'C') === false) {
            get_cookie();
        }
    }
    private function import_post() {
        $http_body = file_get_contents('php://input','r');
        if(!empty($http_body)) {
            $content_type = getenv('HTTP_CONTENT_TYPE');
            if($content_type == 'application/x-www-form-urlencoded') {
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
    private function define_env_constanst() {
        $uri = strtolower($_SERVER['REQUEST_URI']);
        if(($pos = strpos($uri,'?')) !== false) {
            $uri_path = substr($uri,0,$pos);
            $query_string = substr($uri,$pos+1);
        } else {
            $uri_path = $uri;
        }
        $call_page_func = $call_page_name = $prefix_path = '';
        if($this->cfg->uri_mode == 1 && isset($query_string)) {
            $_GET['a'] = empty($_GET['a']) ? '':$_GET['a'];
            $uri_path = dirname($_GET['a']);
            $call_page_func = basename($_GET['a']);
        } else if($this->cfg->uri_mode == 2) { //PATH_INFO mode
            if(empty($_SERVER['PATH_INFO'])) {
                $_SERVER['PATH_INFO'] = str_replace('/'.basename($_SERVER['SCRIPT_FILENAME']),'',$_SERVER['PHP_SELF']);
            }
            $uri_path = dirname($_SERVER['PATH_INFO']);
            $call_page_func = basename($_SERVER['PATH_INFO']);
            if(empty($_SERVER['PATH_INFO'])) $call_page_func = basename($_SERVER['DOCUMENT_URI'],".{$this->cfg->url_file_suffix}");
        } else {
            if($uri_path == '/') {
                $_SERVER['DOCUMENT_URI'] = strtok($this->cfg->web->index,' ');
                $call_page_func = basename($_SERVER['DOCUMENT_URI'],".{$this->cfg->url_file_suffix}");
                $call_page_name = $call_page_func;
            } else if(dirname($uri_path) == '/') {
                $call_page_name = basename($uri_path);
                $call_page_func = basename(strtok($this->cfg->web->index,' '),".{$this->cfg->url_file_suffix}");
            } else {
                $call_page_func = basename($uri_path, ".{$this->cfg->url_file_suffix}");
                $call_page_name = basename(dirname($uri_path));
                $prefix_path = dirname(dirname($uri_path));
                if($prefix_path == '/') $prefix_path = '';
            }
        }
        if(PHP_CLI == false) define('__X_WEB_ROOT__',dirname($_SERVER['SCRIPT_FILENAME']));
        defined('__X_APP_ROOT__') || define('__X_APP_ROOT__',__X_WEB_ROOT__);
        $_ENV['__X_CALL_PAGE_DIR__'] = __X_APP_ROOT__."/{$this->cfg->php_dir_name}";
        $request_method = empty($_SERVER['REQUEST_METHOD']) ? 'g' : substr(strtolower($_SERVER['REQUEST_METHOD']),0,1);
        $add_sub_domain_path = '';
        if($this->cfg->subsite_mode > 0) {
            if($this->cfg->subsite_mode < $this->cfg->subsite_start_level) {
                throw new XException('subsite_start_level not be greater than subsite_mode in your confingure file');
            }
            if($_SERVER['SERVER_ADDR'] != $_SERVER['HTTP_HOST']) {
                if(empty($_SERVER['HTTP_HOST'])) {
                    $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'];
                }
                if(!isip($_SERVER['HTTP_HOST'])) {
                   $sub_domain_list  = explode('.',$_SERVER['HTTP_HOST']);
                    $sub_domain_list = array_reverse($sub_domain_list);
                    $sub_domain_list_count = count($sub_domain_list) -1;
                    if($sub_domain_list_count >= $this->cfg->subsite_start_level) {
                        for($i=$this->cfg->subsite_start_level;$i<=$this->cfg->subsite_mode;$i++) {
                            $add_sub_domain_path = "{$sub_domain_list[$i]}/{$add_sub_domain_path}";
                        }
                    }
                }
            }
        }
        $_ENV['__X_CALL_PAGE_NAME__'] = $call_page_name;
        $_ENV['__X_CALL_PAGE_FILE__'] = "{$_ENV['__X_CALL_PAGE_DIR__']}{$add_sub_domain_path}{$prefix_path}/{$call_page_name}.php";
        $_ENV['__X_CALL_PAGE_FUNC__'] = $request_method.$call_page_func;
        $_ENV['__X_APP_UI_DIR__'] = __X_APP_ROOT__."/{$this->cfg->ui_dir_name}";
        $_ENV['__X_APP_PHP_ERROR_LOG__'] = __X_APP_DATA_DIR__."/{$this->cfg->error_log_dir}/".date('Ymd');
    }
    public function load_application_class_file() {
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
    public function get_html() {
        if($_ENV['__X_EXCEPTION_THROW__'] && $_ENV['__X_FATAL_EXCEPTION__']) {
            $html = $this->exception_string;
        } else {
            $html = $this->app_instance->get_display_html();
            $this->app_instance = null;
            if(PHP_CLI) gc_collect_cycles();
        }
        return $html;
    }
    public function load_cfg() {
        global $_CFG;
        include(__X_FRAMEWORK_ROOT__ . '/config.default.php');
        $this->cfg = $_CFG;
        $cfg_db = $this->cfg->db;
        $cfg_tpl = $this->cfg->tpl;
        $cfg_server = $this->cfg->web;
        $app_config = __X_APP_DATA_DIR__ ."/conf/config.php";
        if(file_exists($app_config)) {
            include($app_config);
            if($this->cfg !== $_CFG) {
                throw new XException("Can not initialize \$_CFG in {$app_config}");
            }
            if($cfg_db !== $_CFG->db) {
                throw new XException("Can not initialize \$_CFG->db in {$app_config}");
            }
            if($cfg_tpl !== $_CFG->tpl) {
                throw new XException("Can not initialize \$_CFG->tpl in {$app_config}");
            }
            if($cfg_server !== $_CFG->web) {
                throw new XException("Can not initialize \$_CFG->server in {$app_config}");
            }
        }
    }
    public function set_time_zone() {
        if(empty($this->cfg->timezone)) {
            throw new XException('Application timezone unset in config file ');
        }
        date_default_timezone_set($this->cfg->timezone);
    }
}

<?php
/**
 * Toknot
 * XTemplate
 *
 * PHP version 5.3
 * 
 * @author chopins xiao <chopins.xiao@gmail.com>
 * @copyright  2012 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://blog.toknot.com
 * @since      File available since Release $id$
 */

exists_frame();
/**
 * XTemplate 
 * 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XTemplate extends XObject {
    /**
     * _var 
     * 
     * @var stdClass
     * @access public
     */
    public $_var = null;

    /**
     * TPL_INI 
     * 
     * @var stdClass
     * @access public
     */
    public $TPL_INI = null;

    /**
     * T 
     * 
     * @var XTemplateObject
     * @access public
     */
    public $T = null;

    /**
     * out_html 
     * the view-class output html
     * 
     * @var string
     * @access private
     */
    private $out_html = '';

    /**
     * new_complie 
     * 
     * @var mixed
     * @access private
     */
    private $new_complie = true;

    /**
     * tst 
     * 
     * @var string
     * @access private
     */
    private $tst = "\n\$this->out_html.=<<<XTHTML\n";

    /**
     * tnd 
     * 
     * @var string
     * @access private
     */
    private $tnd = "\nXTHTML;\n";

    /**
     * cache_dir 
     * 
     * @var string
     * @access private
     */
    private $cache_dir = null;

    /**
     * singleton 
     * 
     * @param mixed $TPL_INI 
     * @static
     * @access public
     * @return void
     */
    public static function singleton($TPL_INI) {
        $ins = parent::__singleton();
        $ins->set_ini($TPL_INI);
        return $ins;
    }

    /**
     * set_ini 
     * 
     * @param mixed $TPL_INI 
     * @access private
     * @return void
     */
    private function set_ini($TPL_INI) {
        $this->TPL_INI = $TPL_INI;
    }

    /**
     * __construct 
     * 
     * @access protected
     * @return void
     */
    protected function __construct() {}

    /**
     * execute 
     * 
     * @param XTemplateObject $T 
     * @param XStdClass $D 
     * @access public
     * @return void
     */
    public function execute(XTemplateObject $T, XStdClass $D) {
        $this->T = $T;
        $this->check_t_properties();
        $this->_var = $D;
        $this->fetch_templete();
        $this->display();
        $this->create_cache();
    }

    /**
     * get_cache 
     * 
     * @param mixed $T 
     * @access public
     * @return mixed
     */
    public function get_cache($T) {
        if($T->static_cache) {
            $cache_dirname = $this->TPL_INI->html_cache_dirname;
        } else {
            $cache_dirname = $this->TPL_INI->data_cache_dirname;
        }
        $cache_dir = "{$this->cache_dir}/{$cache_dirname}";
        $cache_file = "{$cache_dir}/{$T->name}.{$T->type}";
        $current_time = time();
        if(!file_exists($cache_file) || filemtime($cache_file) + $T->cache_time < $current_time) {
            return false;
        }
        $cache_data = file_get_contents($cache_file);
        if($T->data_cache) {
            $D = unserialize($cache_data);
            $this->execute($T,$D);
        } elseif($T->static_cache) {
            $this->out_html = $cache_data;
        } else {
            return false;
        }
        return true;
    }

    /**
     * set_cache_dir 
     * 
     * @param mixed $dir 
     * @access public
     * @return void
     */
    public function set_cache_dir($dir) {
        $this->cache_dir = $dir;
        if(!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0700, true);
        }
    }

    /**
     * check_t_properties 
     * 
     * @access private
     * @return void
     */
    private function check_t_properties() {
        if(!isset($this->T->name)) {
            throw new XException('muset be set template name , use $this->T->name be set');
        }
        if(!isset($this->T->type)) {
            throw new XException('muset be set template filetype , use $this->T->type be set');
        }
        if(isset($this->T->data_cache) || isset($this->T->static_cache)) { 
            if(!isset($this->T->cache_time)) {
                $this->T->cache_time = 300;
            } elseif(isset($this->T->cache_time)) {
                $this->T->cache_time = (int)$this->T->cache_time;
                if($this->T->cache_time < 1) $this->T->cache_time = 1;
            }
        } else {
            $this->T->data_cache = false;
            $this->T->static_cache = false;
        }
    }

    /**
     * display 
     * 
     * @param mixed $obc 
     * @access public
     * @return void
     */
    public function display($obc=true) {
        list($cache_file,$tpl_file) = $this->get_tpl_path();
        if(!file_exists($cache_file)) {
            $this->fetch_templete();
        }
        $comp_file_time = file_exists($cache_file) ? filemtime($cache_file):0;
        $guess_path_time = filemtime($tpl_file);

        if(PHP_SAPI == 'cli' && $comp_file_time <= $guess_path_time) {
            check_syntax($cache_file);
        }
        $this->_var->__X_RUN_TIME__ = 'Processed: '. (microtime(true) - __X_RUN_START_TIME__) . " seconds<br />";
        if(__X_FIND_SLOW__) {
            $slow_pointer = find_php_slow_pointer(true);
            unregister_tick_function('find_php_slow_pointer');
            $this->_var->__X_RUN_TIME__.= '<div><b>PHP Slow Pointer:</b><br />';
            if(is_array($slow_pointer)) {
                foreach($slow_pointer as $msg) {
                    $this->_var->__X_RUN_TIME__.=$msg;
                }
            }
            $this->_var->__X_RUN_TIME__.='</div>';
        }
        include($cache_file);
    }

    /**
     * get_html 
     * 
     * @access public
     * @return void
     */
    public function get_html() {
        return $this->out_html;
    }

    /**
     * get_tpl_path 
     * 
     * @access public
     * @return void
     */
    public function get_tpl_path() {
        if(empty($this->T->type)) $this->T->type = 'html';
        switch($this->T->type) {
            case 'json':
                $tpl_name = "{$this->T->name}{$this->TPL_INI->json_suffix}";
            break;
            case 'xml':
                $tpl_name ="{$this->T->name}{$this->TPL_INI->xml_suffix}";
            break;
            default:
                $tpl_name = "{$this->T->name}{$this->TPL_INI->html_suffix}";
            break;
        }
        $cache_name = $this->TPL_INI->compile_tpl_dir_name;
        $cache_file = __X_APP_DATA_DIR__."/{$cache_name}/{$tpl_name}.php";
        $tpl_file = "{$_ENV['__X_APP_UI_DIR__']}/{$this->T->type}/{$tpl_name}";
        return array($cache_file,$tpl_file);
    }

    /**
     * fetch_templete 
     * 
     * @access public
     * @return void
     */
    public function fetch_templete() {
        list($cache_file,$tpl_file) = $this->get_tpl_path();
        if(!file_exists($tpl_file)) {
            throw new XException("UI File {$tpl_file} not exists");
        }
        $comp_file_time = file_exists($cache_file) ? filemtime($cache_file):0;
        $guess_path_time = filemtime($tpl_file);
        $this->new_complie = false;
        is_dir(dirname($cache_file)) or mkdir(dirname($cache_file), 0700, true);
        if(__X_SHOW_ERROR__ || $comp_file_time <= $guess_path_time) {
            $this->new_complie = true;
            if($this->T->type == 'json') {
                $this->parse_json_tpl($tpl_file,$cache_file);
            } else {
                $this->parse_html_tpl($tpl_file, $cache_file);
            }
        }
    }

    /**
     * parse_json_tpl 
     * 
     * @param mixed $tpl_file 
     * @param mixed $comp_file 
     * @access private
     * @return void
     */
    private function parse_json_tpl($tpl_file,$comp_file) {
        $file_str = file_get_contents($tpl_file);
        $type = strtolower(trim(strtok($file_str,'<<<')));
        switch($type) {
            case 'form':
            $json = $this->parse_json_form($file_str);
            break;
            $json = $this->parse_json_table($file_str);
            case 'table':
            break;
        }
        file_put_contents($comp_file, $json);
    }

    /**
     * parse_json_form 
     * 
     * @param mixed $file_str 
     * @access private
     * @return void
     */
    private function parse_json_form($file_str) {
        $json_arr = array();
        $type = strtok($file_str,'<<<');
        $title = trim(strtok('<<<'));
        $json_arr['title'] = $title == '_' ? '' : $title;
        $input = trim(strtok('|'));
        switch($input) {
            case 'text':
            break;
        }
    }

    /**
     * inc_tpl 
     * 
     * @param mixed $tpl 
     * @access public
     * @return void
     */
    public function inc_tpl($tpl) {
        $this->T->name = $tpl;
        $this->get_tpl_path();
        $this->fetch_templete();
        $this->display(false);
    }

    /**
     * parse_html_tpl 
     * 
     * @param mixed $tpl_file 
     * @param mixed $comp_file 
     * @access private
     * @return void
     */
    private function parse_html_tpl($tpl_file,$comp_file) {
        $file_str = file_get_contents($tpl_file);
        $this->parse_var($file_str);
        $this->echo_value($file_str);
        $this->call_func($file_str);
        $this->parse_inc($file_str);
        $this->parse_js($file_str);
        $this->parse_css($file_str);
        $this->parse_if($file_str);
        $this->parse_foreach($file_str);
        $this->parse_set($file_str);
        $this->parse_uri($file_str);
        if($this->TPL_INI->compression) {
            $this->del_html_comment($file_str);
            $file_str = preg_replace('/[\n\t\r]+/i','',$file_str);
        }
        $file_str = "<?php {$this->tst}$file_str{$this->tnd}";
        file_put_contents($comp_file, $file_str);
    }

    /**
     * parse_inc 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_inc(&$str) {
        $str = preg_replace('/\{inc\s+([a-zA-Z0-9_]+)\}/i',$this->tnd.'$this->inc_tpl("$1");'.$this->tst,$str);
    }

    /**
     * parse_foreach 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_foreach(&$str) {
        $str = preg_replace('/\{foreach\s+(\$\S+)\s+as(.*)\}/',$this->tnd.
                'if(is_array($1) && !empty($1)){ foreach($1 as $2) { '.$this->tst,$str);
        $str = str_replace('{/foreach}',$this->tnd.'}}'.$this->tst,$str);
    }

    /**
     * del_html_comment 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function del_html_comment(&$str) {
        $str = preg_replace('/<!--.*-->/i','',$str);
    }

    /**
     * parse_set 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_set(&$str) {
        $str = preg_replace('/\{set\s+([^\{^\}]+)}/i',$this->tnd.'$1;'.$this->tst,$str);
    }

    /**
     * parse_if 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_if(&$str) {
        $str = preg_replace('/\{if\s+([^\{^\}]+)\}/i',$this->tnd.'if($1) {'.$this->tst,$str);
        $str = preg_replace('/\{elseif\s+([^\}\{]+)\}/i',$this->tnd.'} elseif($1){'.$this->tst, $str);
        $str = str_replace('{else}',$this->tnd.'} else {'.$this->tst,$str);
        $str = str_replace('{/if}',$this->tnd.'}'.$this->tst,$str);
    }

    /**
     * parse_var 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_var (&$str) {
        $str = preg_replace('/\$([a-zA-Z_]\w*)\.(\w+)\.([\w]+)/i', '\$$1[\'$2\'][\'$3\']',$str);
        $str = preg_replace('/\$([a-zA-Z_]\w*)\.(\w+)/i', '\$$1[\'$2\']',$str);
        $str = preg_replace('/\$([A-Za-z_]\w*)/i','\$this->_var->$1',$str);
    }

    /**
     * inc_js 
     * 
     * @param mixed $file 
     * @access private
     * @return void
     */
    private function inc_js($file) {
        $file_path = "{$_ENV['__X_APP_UI_DIR__']}/{$this->TPL_INI->js_file_dir}/{$file}.js";
        if(!file_exists($file_path)) throw new XException("{$file_path} not exists");
        $o_change_time = filemtime($file_path);
        $output_path  = "{$this->TPL_INI->static_dir_name}/{$file}.js";
        $w_change_time = file_exists($output_path) ? filemtime($output_path) : '0';
        if($this->TPL_INI->compression && $w_change_time <= $o_change_time) {
            $js_packer = new XJSPacker($file_path);
            $js_file_str = $js_packer->get_str();
            file_put_contents($output_path,$js_file_str);
        } else if($w_change_time <= $o_change_time) {
            $js_file_str = file_get_contents($file_path);
            file_put_contents($output_path,$js_file_str);
        }
        $domain  = empty($this->TPL_INI->http_access_static_domain) ? '': "http://{$this->TPL_INI->http_access_static_domain}";
        $this->out_html .= "<script type=\"text/javascript\" src=\"{$domain}{$this->TPL_INI->http_access_static_path}/{$file}.js\"></script>";
    }

    /**
     * parse_js 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_js(&$str) {
        $str = preg_replace('/\{js\s+([a-zA-Z0-9_]+)\}/i',$this->tnd.'$this->inc_js("$1");'.$this->tst,$str);
    }

    /**
     * inc_css 
     * 
     * @param mixed $file 
     * @access private
     * @return void
     */
    private function inc_css($file) {
        $file_path = "{$_ENV['__X_APP_UI_DIR__']}/{$this->TPL_INI->css_file_dir}/{$file}.css";
        if(!file_exists($file_path)) throw new XException("{$file_path} not exists");
        $o_change_time = filemtime($file_path);
        $output_path  = "{$this->TPL_INI->static_dir_name}/{$file}.css";
        $w_change_time = file_exists($output_path) ? filemtime($output_path) : '0';
        if($this->TPL_INI->compression && $w_change_time <= $o_change_time) {
            $css_packer = new XCSSPacker($file_path);
            $css_file_str = $css_packer->get_str();
            file_put_contents($output_path,$css_file_str);
        } else {
            $css_file_str = file_get_contents($file_path);
            file_put_contents($output_path,$css_file_str);
        }
        $domain  = empty($this->TPL_INI->http_access_static_domain) ? '': "http://{$this->TPL_INI->http_access_static_domain}";
        $this->out_html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$domain}{$this->TPL_INI->http_access_static_path}/{$file}.css\">";
    }

    /**
     * parse_css 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_css(&$str) {
        $str = preg_replace('/\{css\s+([a-zA-Z0-9_]+)\}/i',$this->tnd.'$this->inc_css("$1");'.$this->tst,$str);
    }

    /**
     * parse_uri 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function parse_uri(&$str) {
        $str = preg_replace_callback('/\{url=(.*)\}/i',array($this,'replace_url'),$str);
    }

    /**
     * replace_url 
     * 
     * @param mixed $matches 
     * @access private
     * @return void
     */
    private function replace_url($matches) {
        if(preg_match('/\$/',$matches[1])) {
            $str = preg_replace('/(\$[a-zA-Z0-9_\[\]\'\->]+)/i','{$1}',$matches[1]);
            return "<?php \$this->echo_url(\"$str\")?>";
        } else {
            return "<?php \$this->echo_url('{$matches[1]}')?>";
        }
    }

    /**
     * echo_url 
     * 
     * @param mixed $uri 
     * @access public
     * @return void
     */
    public function echo_url($uri) {
        $uri_str = explode('?',$uri);
        $uri = empty($uri_str[0]) ? '' : $uri_str[0];
        $params = empty($uri_str[1]) ? '' : $uri_str[1];
        echo support_url_mode($uri,$params);
    }

    /**
     * echo_value 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function echo_value(&$str) {
        $str = preg_replace('/\{\$([a-zA-Z_][^\}\{]+)\}/i','{\$$1}',$str);
    }

    /**
     * call_func 
     * 
     * @param mixed $str 
     * @access private
     * @return void
     */
    private function call_func(&$str) {
        $str = preg_replace('/\{func\s+([a-zA-Z_\d]+)\((.*)\)\}/i',
                $this->tnd.'if(function_exists(\'$1\')){ $this->out_html.=$1($2);} elseif(method_exists($this,\'$1\')) {
                $this->out_html.=$this->$1($2);}'.$this->tst,$str);
    }

    /**
     * __destruct 
     * 
     * @access public
     * @return void
     */
    public function __destruct() {
        unset($this->_var);
        unset($this);
        unset($this->out_html);
    }

    /**
     * create_cache 
     * 
     * @access public
     * @return void
     */
    public function create_cache() {
        if($this->T->static_cache || $this->T->data_cache) {
            if($this->T->static_cache) {
                $cache_dirname = $this->TPL_INI->html_cache_dirname;
                $cache_string = $this->out_html;
            } else {
                $cache_dirname = $this->TPL_INI->data_cache_dirname;
                $cache_string = serialize($this->_var);
            }
            $cache_dir = "{$this->cache_dir}/{$cache_dirname}";
            if(!is_dir($cache_dir)) {
                mkdir($cache_dir, 0700,true);
            }
            $cache_file = "{$cache_dir}/{$this->T->name}.{$this->T->type}";
            file_put_contents($cache_file, $cache_string);
        }
    }
}

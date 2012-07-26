<?php
/**
 * Toknot
 * XTemplate
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
 * XTemplate 
 * 
 * @package 
 * @version $id$
 * @author Chopins xiao <chopins.xiao@gmail.com> 
 */
class XTemplate {
    public $_var = null;
    public $cfg = array();
    public $ajx_return_data = null;
    public $T = null;
    private $fetch_ready = false;
    private $out_html = '';
    private $new_complie = true;
    private $tst = "\n\$this->out_html.=<<<XTHTML\n";
    private $tnd = "\nXTHTML;\n";
    public function __construct($cfg,$T,$D) {
        $this->cfg = $cfg;
        $this->T = $T;
        if(isset($this->T->name)) {
            $this->T->notpl = null;
        }
        $this->_var = $D;
    }
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
        include($cache_file);
    }
    public function get_html() {
        return $this->out_html;
    }
    public function get_tpl_path() {
        if(empty($this->T->type)) $this->T->type = 'html';
        switch($this->T->type) {
            case 'json':
                $tpl_name = "{$this->T->name}{$this->cfg->tpl_json_suffix}";
            break;
            case 'xml':
                $tpl_name ="{$this->T->name}{$this->cfg->tpl_xml_suffix}";
            break;
            default:
                $tpl_name = "{$this->T->name}{$this->cfg->tpl_html_suffix}";
            break;
        }
        $cache_name = $this->cfg->tpl_compile_tpl_dir_name;
        $cache_file = __X_APP_DATA_DIR__."/{$cache_name}/{$tpl_name}.php";
        $tpl_file = "{$_ENV['__X_APP_UI_DIR__']}/{$this->T->type}/{$tpl_name}";
        return array($cache_file,$tpl_file);
    }
    public function fetch_templete() {
        list($cache_file,$tpl_file) = $this->get_tpl_path();
        if(!file_exists($tpl_file)) {
            throw new XException("UI File {$tpl_file} not exists");
        }
        $comp_file_time = file_exists($cache_file) ? filemtime($cache_file):0;
        $guess_path_time = filemtime($tpl_file);
        $this->new_complie = false;
        is_dir(dirname($cache_file)) or amkdir(dirname($cache_file));
        if(__X_SHOW_ERROR__ || $comp_file_time <= $guess_path_time) {
            $this->new_complie = true;
            if($this->T->type == 'json') {
                $this->parse_json_tpl($tpl_file,$cache_file);
            } else {
                $this->parse_html_tpl($tpl_file, $cache_file);
            }
        }
    }
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
    public function inc_tpl($tpl) {
        $this->T->name = $tpl;
        $this->get_tpl_path();
        $this->fetch_templete();
        $this->display(false);
    }
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
        if($this->cfg->tpl_compression) {
            $this->del_html_comment($file_str);
            //$file_str = preg_replace('/[\n\t\r]+/i','',$file_str);
        }
        $file_str = "<?php {$this->tst}$file_str{$this->tnd}";
        file_put_contents($comp_file, $file_str);
    }
    private function parse_inc(&$str) {
        $str = preg_replace('/\{inc\s+([a-zA-Z0-9_]+)\}/i',$this->tnd.'$this->inc_tpl("$1");'.$this->tst,$str);
    }
    private function parse_foreach(&$str) {
        $str = preg_replace('/\{foreach\s+(\$\S+)\s+as(.*)\}/',$this->tnd.
                'if(is_array($1) && !empty($1)){ foreach($1 as $2) { '.$this->tst,$str);
        $str = str_replace('{/foreach}',$this->tnd.'}}'.$this->tst,$str);
    }
    private function del_html_comment(&$str) {
        $str = preg_replace('/<!--.*-->/i','',$str);
    }
    private function parse_set(&$str) {
        $str = preg_replace('/\{set\s+([^\{^\}]+)}/i',$this->tnd.'$1;'.$this->tst,$str);
    }
    //解析if else 结构
    private function parse_if(&$str) {
        $str = preg_replace('/\{if\s+([^\{^\}]+)\}/i',$this->tnd.'if($1) {'.$this->tst,$str);
        $str = preg_replace('/\{elseif\s+([^\}\{]+)\}/i',$this->tnd.'} elseif($1){'.$this->tst, $str);
        $str = str_replace('{else}',$this->tnd.'} else {'.$this->tst,$str);
        $str = str_replace('{/if}',$this->tnd.'}'.$this->tst,$str);
    }
    //将变量进行转换
    private function parse_var (&$str) {
        $str = preg_replace('/\$([a-zA-Z_]\w*)\.(\w+)\.([\w]+)/i', '\$$1[\'$2\'][\'$3\']',$str);
        $str = preg_replace('/\$([a-zA-Z_]\w*)\.(\w+)/i', '\$$1[\'$2\']',$str);
        $str = preg_replace('/\$([A-Za-z_]\w*)/i','\$this->_var->$1',$str);
    }
    private function inc_js($file) {
        $file_path = "{$_ENV['__X_APP_UI_DIR__']}/{$this->cfg->tpl_js_file_dir}/{$file}.js";
        if(!file_exists($file_path)) throw new XException("{$file_path} not exists");
        $o_change_time = filemtime($file_path);
        $output_path  = "{$this->cfg->tpl_static_dir_name}/{$file}.js";
        $w_change_time = file_exists($output_path) ? filemtime($output_path) : '0';
        if($this->cfg->tpl_compression && $w_change_time <= $o_change_time) {
            $js_packer = new XJSPacker($file_path);
            $js_file_str = $js_packer->get_str();
            file_put_contents($output_path,$js_file_str);
        } else if($w_change_time <= $o_change_time) {
            $js_file_str = file_get_contents($file_path);
            file_put_contents($output_path,$js_file_str);
        }
        $domain  = empty($this->cfg->tpl_http_access_static_domain) ? '': "http://{$this->cfg->tpl_http_access_static_domain}";
        $this->out_html .= "<script type=\"text/javascript\" src=\"{$domain}{$this->cfg->tpl_http_access_static_path}/{$file}.js\"></script>";
    }
    private function parse_js(&$str) {
        $str = preg_replace('/\{js\s+([a-zA-Z0-9_]+)\}/i',$this->tnd.'$this->inc_js("$1");'.$this->tst,$str);
    }
    private function inc_css($file) {
        $file_path = "{$_ENV['__X_APP_UI_DIR__']}/{$this->cfg->tpl_css_file_dir}/{$file}.css";
        if(!file_exists($file_path)) throw new XException("{$file_path} not exists");
        $o_change_time = filemtime($file_path);
        $output_path  = "{$this->cfg->tpl_static_dir_name}/{$file}.css";
        $w_change_time = file_exists($output_path) ? filemtime($output_path) : '0';
        if($this->cfg->tpl_compression && $w_change_time <= $o_change_time) {
            $css_packer = new XCSSPacker($file_path);
            $css_file_str = $css_packer->get_str();
            file_put_contents($output_path,$css_file_str);
        } else {
            $css_file_str = file_get_contents($file_path);
            file_put_contents($output_path,$css_file_str);
        }
        $domain  = empty($this->cfg->tpl_http_access_static_domain) ? '': "http://{$this->cfg->tpl_http_access_static_domain}";
        $this->out_html .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$domain}{$this->cfg->tpl_http_access_static_path}/{$file}.css\">";
    }
    private function parse_css(&$str) {
        $str = preg_replace('/\{css\s+([a-zA-Z0-9_]+)\}/i',$this->tnd.'$this->inc_css("$1");'.$this->tst,$str);
    }
    private function parse_uri(&$str) {
        $str = preg_replace_callback('/\{url=(.*)\}/i',array($this,'replace_url'),$str);
    }
    private function replace_url($matches) {
        if(preg_match('/\$/',$matches[1])) {
            $str = preg_replace('/(\$[a-zA-Z0-9_\[\]\'\->]+)/i','{$1}',$matches[1]);
            return "<?php \$this->echo_url(\"$str\")?>";
        } else {
            return "<?php \$this->echo_url('{$matches[1]}')?>";
        }
    }
    public function echo_url($uri) {
        $uri_str = explode('?',$uri);
        $uri = empty($uri_str[0]) ? '' : $uri_str[0];
        $params = empty($uri_str[1]) ? '' : $uri_str[1];
        echo support_url_mode($uri,$params);
    }
    //解析所有可输出变量
    private function echo_value(&$str) {
        $str = preg_replace('/\{\$([a-zA-Z_][^\}\{]+)\}/i','{\$$1}',$str);
    }
    //调用函数
    private function call_func(&$str) {
        $str = preg_replace('/\{func\s+([a-zA-Z_\d]+)\((.*)\)\}/i',
                $this->tnd.'if(function_exists(\'$1\')){ $this->out_html.=$1($2);} elseif(method_exists($this,\'$1\')) {
                $this->out_html.=$this->$1($2);}'.$this->tst,$str);
    }
    public function show_page_num() {
        echo '<div class="PageNum"><span>共计'.$this->T->record_num.'条记录</span><span>共计'.$this->T->page_num.'页</span>';
        if($this->T->current <= 5) {
            $start = 1;
        } else {
            $start = $this->T->current -5;
        }
        if($this->T->page_num <=5) {
            $max = $this->T->page_num +1;
        } elseif($this->T->page_num > $this->T->current +5) {
            $max = $this->T->current +5;
        } else {
            $max = $this->T->page_num;
        }
        for($i=$start;$i<$max;$i++) {
            if($i == $this->T->current) {
            echo "<span class=\"current\">$i</span>";
            } else {
                $vist_path = '';
                if(isset($_GET['c'])) {
                    $cls = $_GET['c'];
                    unset($_GET['c']);
                }
                if(isset($GET['m'])) {
                    $m = $_GET['m'];
                    unset($_GET['m']);
                } else {
                    $m = '';
                }
                $_GET['p'] = $i;
                $params = implode('&',$_GET);
                $url = support_url_mode($cls,$m,$params,$this->cfg->uri_mode);
                echo "<span><a href=\"{$url}\">$i</a></span>";
            }
        }
        echo '</div>';
    }
    public function __destruct() {
        //$this->create_static($this->global_data); 
    }
    public function create_static($global_data = null) {
        if(isset($global_data['cache_file'])) {
            if(!$this->cache_id) {
                $this->cache_id = $global_data['cache_id'];
            } else {
                file_put_contents(__X_APP_DATA_DIR__.'/cache_html/' . $global_data['cache_file']);
                $this->cache_id = false;
           }
       }
    }
}

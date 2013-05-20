<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Di\Object;
use Toknot\Exception\StandardException;
use Toknot\Di\ArrayObject;
use Toknot\Di\FileObject;

class Renderer extends Object {

    private $varList = null;
    private $tplName = '';
    
    /**
     * Set template file extension name
     * 
     * @var string 
     * @access public
     */
    public $fileExtension = 'htm';
    
    /**
     * Set template file path, usual the path is Application of View layer path
     *
     * @var string
     * @access public
     */
    public $scanPath = '';
    
    /**
     * Set template file be transfrom to php file save path, usual the path is Application of Data View path
     *
     * @var string
     * @access public
     */
    public $cachePath = '';
    private $transfromFile = '';
    private $htmlCacheFile = '';
    
    /**
     * Set whether enable HTML static cache, if set true is enable and must set Renderer::$htmlCachePath
     *
     * @var boolean
     */
    public $enableHTMLCache = false;
    
    /**
     * set HTML static cache save path when enable HTML cache
     *
     * @var string
     * @access public
     */
    public $htmlCachePath = null;
    
    /**
     * set output HTML static cache of threshold time, if one REQUEST query is same and 
     * twice request time of interval less the value, will output exists HTML file
     *
     * @var int
     * @access public
     */
    public $outCacheThreshold = 2;
    
    protected function __construct() {
        ;
    }

    public static function singleton() {
        return parent::__singleton();
    }

    public function importVars($vars) {
        $this->varList = new ArrayObject($vars);
    }

    public function display($tplName) {
        $this->tplName = $this->scanPath . '/' . $tplName . '.' . $this->fileExtension;
        if (!file_exists($this->tplName)) {
            throw new StandardException("{$this->tplName} not exists");
        }
        
        //HTML cache control
        if ($this->enableHTMLCache && $this->htmlCachePath != null) {
            $key = md5($_SERVER['QUERY_STRING']);
            $this->htmlCacheFile = $this->htmlCachePath . '/' . $tplName . '.' .$key. '.html';
            if(file_exists($this->htmlCacheFile)) {
                $mtime = filemtime($this->htmlCacheFile);
                if($mtime + $this->outCacheThreshold <= time()) {
                    return include_once $this->htmlCacheFile;
                }
            }
            ob_start();
        }
        
        $this->transfromFile = $this->cachePath . '/' . $tplName . '.php';
        if (!file_exists($this->transfromFile) ||
                filemtime($this->transfromFile) < filemtime($this->tplName)) {
            $this->transfromToPHP();
        }
        
        include_once $this->transfromFile;
        
        //HTML Cache write
        if ($this->enableHTMLCache && $this->htmlCachePath != null) {
            $html = ob_get_contents();
            ob_flush();
            ob_end_clean();
            FileObject::saveContent($this->htmlCacheFile, $html);
        }
    }

    private function transfromToPHP() {
        $content = file_get_contents($this->tplName);

        //transfrom variable
        $content = preg_replace_callback('/\{\$(\.a-zA-Z0-9_\x7f-\xff]+)\}/i', function($matches) {
                    $str = '<?php echo $this->varList->';
                    $str .= str_replace('.', '->', $matches[1]);
                    $str .= ';?>';
                    return $str;
                }, $content);

        //transfrom foreach statement
        $content = preg_replace_callback('/\{foreach\s+\$(\S+)\s+as(.*)\}/i', function($matches) {
                    $matches[1] = str_replace('.', '->', $matches[1]);
                    $str = "<?php if(is_array(\$this->varList->{$matches[1]}) && !empty(\$this->varList->{$matches[1]})){";
                    $str .= "foreach(\$this->varList->$matches[1] as $matches[2]) { ?>";
                    return $str;
                }, $content);
        $content = str_replace('{/foreach}', '<?php }} ?>', $content);

        //transfrom define variable which is not controller set
        $content = preg_replace('/\{set\s+([^\{^\}]+)\}/i', "<?php $1;?>", $content);

        //transfrom if statement
        $content = preg_replace_callback('/\{if\s+([^\{^\}]+)\}/i', function($matches) {
                    $matches[1] = str_replace('/\$([\[\]a-zA-Z0-9_\x7f-\xff]+)/i', '$this->varList->$1', $matches[1]);
                    $matches[1] = str_replace('.', '->', $matches[1]);
                    return "<?php if($matches[1]) { ?>";
                }, $content);
        $content = preg_replace_callback('/\{elseif\s+([^\}\{]+)\}/i', function($matches) {
                    $matches[1] = str_replace('/\$([\[\]a-zA-Z0-9_\x7f-\xff]+)/i', '$this->varList->$1', $matches[1]);
                    $matches[1] = str_replace('.', '->', $matches[1]);
                    return "<?php } elseif({$matches[1]}){ ?>";
                }, $content);
        $content = str_replace('{else}', '<?php } else { ?>', $content);
        $content = str_replace('{/if}', '<?php } ?>', $content);

        //import other template file
        $content = preg_replace('/\{inc\s+(\w+)\}/i', '<?php $this->importFile("$1"); ?>', $content);

        //transfrom invoke php function and echo return value
        $content = preg_replace_callback('/\{func\s+([a-zA-Z_\d]+)\((.*)\)\}/i', function ($matches) {
                    $matches[2] = str_replace('.', '->', $matches[2]);
                    $matches[2] = str_replace('/\$([\[\]a-zA-Z0-9_\x7f-\xff]+)/i', '$this->varList->$1', $matches[2]);
                    return "<?php if(function_exists({$matches[1]})){ echo {$matches[1]}({$matches[2]});} ?>";
                }, $content);
        
        //clean the whitespace from beginning and end of line and html comment
        $content = preg_replace('/^\s*|\s*$|<!--.*-->|[\n\t\r]+/m','', $content);
        
        FileObject::saveContent($this->transfromFile, $content);
    }

    public function importFile($file) {
        $this->display($file);
    }
}
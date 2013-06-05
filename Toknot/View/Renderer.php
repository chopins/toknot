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
use Toknot\Di\DataCacheControl;

class Renderer extends Object {

    private $varList = null;
    private $tplName = '';

    /**
     * Set template file extension name
     * 
     * @var string 
     * @access public
     * @static
     */
    public static $fileExtension = 'htm';

    /**
     * Set template file path, usual the path is Application of View layer path
     *
     * @var string
     * @access public
     * @static
     */
    public static $scanPath = '';

    /**
     * Set template file be transfrom to php file save path, usual the path is Application of Data View path
     *
     * @var string
     * @access public
     * @static
     */
    public static $cachePath = '';
    private $transfromFile = '';

    /**
     * Set whether enable HTML static cache, if set true is enable and must set Renderer::$htmlCachePath
     *
     * @var boolean
     */
    public static $enableCache = false;

    /**
     * set HTML static cache save path when enable HTML cache
     *
     * @var string
     * @access public
     */
    public static $htmlCachePath = null;
    public static $dataCachePath = null;

    /**
     * set output HTML static cache of threshold time, if one REQUEST query is same and 
     * twice request time of interval less the value, will output exists HTML file
     *
     * @var int
     * @access public
     */
    public static $outCacheThreshold = 2;
    
    /**
     * set cache type
     *
     * @var integer
     */
    public static $cacheFlag = 1;
    
    /**
     * cache page to html
     */
    const CACHE_FLAG_HTML = 1;
    
    /**
     * only cache data of be invoked controller without your construct method data
     */
    const CACHE_FLAG_DATA = 2;
    
    const CACHE_USE_SUCC = 200;
    protected function __construct() {
        $this->varList = new ArrayObject;
    }

    public static function singleton() {
        return parent::__singleton();
    }

    /**
     * import variable of template
     * 
     * @param array $vars
     */
    public function importVars($vars) {
        $this->varList->importPropertie($vars);
    }

    public function outPutHTMLCache($tplName) {
        $result = $this->display($tplName);
        if ($result === self::CACHE_USE_SUCC)
            return true;
        return false;
    }

    public function display($tplName) {
        $this->tplName = self::$scanPath . '/' . $tplName . '.' . self::$fileExtension;
        if (!file_exists($this->tplName)) {
            throw new StandardException("{$this->tplName} not exists");
        }

        //HTML cache control
        if (self::$enableCache && self::$htmlCachePath != null) {
            $key = md5($_SERVER['QUERY_STRING']);
            if (self::$cacheFlag == self::CACHE_FLAG_HTML) {
                $htmlCacheFile = self::$htmlCachePath . '/' . $tplName . '.' . $key . '.html';
                if (file_exists($htmlCacheFile)) {
                    $mtime = filemtime($htmlCacheFile);
                    if ($mtime + self::$outCacheThreshold <= time()) {
                        include_once $htmlCacheFile;
                        return self::CACHE_USE_SUCC;
                    }
                    ob_start();
                }
            } elseif(self::$cacheFlag == self::CACHE_FLAG_DATA) {
                $dataCacheFile = self::$dataCachePath . '/' . $tplName . '.' . $key;
                $cache = new DataCacheControl($dataCacheFile);
                $cache->useExpire(self::$outCacheThreshold);
                $varList = $cache->get();
                if($varList === false) {
                    $cacheData = $this->varList->transformToArray();
                    $cache->save($cacheData);
                } else {
                    $cacheVar = new ArrayObject($varList);
                    $cacheVar->importPropertie($this->varList);
                    $this->varList = $cacheVar;
                    uset($cacheVar,$varList);
                }
            }
        }

        $this->transfromFile = $this->cachePath . '/' . $tplName . '.php';
        if (!file_exists($this->transfromFile) ||
                filemtime($this->transfromFile) < filemtime($this->tplName)) {
            $this->transfromToPHP();
        }

        include_once $this->transfromFile;

        //HTML Cache write
        if (self::$enableCache && self::$htmlCachePath != null && self::$cacheFlag == self::CACHE_FLAG_HTML) {
            $html = ob_get_contents();
            ob_flush();
            ob_end_clean();
            FileObject::saveContent($htmlCacheFile, $html);
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
        $content = preg_replace('/^\s*|\s*$|<!--.*-->|[\n\t\r]+/m', '', $content);

        FileObject::saveContent($this->transfromFile, $content);
    }

}
<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\View;

use Toknot\Di\Object;
use Toknot\Exception\StandardException;
use Toknot\Di\ArrayObject;
use Toknot\Di\FileObject;
use Toknot\Di\DataCacheControl;
use Toknot\View\ViewCache;

class Renderer extends Object {

	private $varList = null;

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

	protected function __init() {
		$this->varList = new ViewData;
	}

	public static function singleton() {
		return parent::__singleton();
	}

	/**
	 * import variable of template
	 * 
	 * @param array|Toknot\Di\ArrayObject $vars
	 */
	public function importVars(& $vars) {
		if ($vars instanceof ViewData) {
			$this->varList = $vars;
		} else {
			$this->varList->importPropertie($vars);
		}
	}

	public function outPutHTMLCache($tplName) {
		$result = $this->display($tplName);
		if ($result === self::CACHE_USE_SUCC)
			return true;
		return false;
	}

	public function display($tplName) {
		$tplFile = self::$scanPath . '/' . $tplName . '.' . self::$fileExtension;
		if (!file_exists($tplFile)) {
			throw new StandardException("{$tplFile} not exists");
		}
		$useCache = false;
		//HTML cache control
		if (self::$enableCache && self::$htmlCachePath != null) {
			$key = md5($_SERVER['QUERY_STRING']);
			if (self::$cacheFlag == self::CACHE_FLAG_HTML) {
				$htmlCacheFile = self::$htmlCachePath . '/' . $tplName . '.' . $key . '.html';
				if (file_exists($htmlCacheFile)) {
					$mtime = filemtime($htmlCacheFile);
					if ($mtime + (string) self::$outCacheThreshold <= time()) {
						include_once $htmlCacheFile;
						return ViewCache::CACHE_USE_SUCC;
					}
					ob_start();
				}
			} elseif (self::$cacheFlag == self::CACHE_FLAG_DATA) {
				$dataCacheFile = self::$dataCachePath . '/' . $tplName . '.' . $key;
				$cache = new DataCacheControl($dataCacheFile);
				$cache->useExpire(self::$outCacheThreshold);
				$varList = $cache->get();
				if ($varList === false) {
					$cacheData = $this->varList->transformToArray();
					$cache->save($cacheData);
				} else {
					$cacheVar = new ArrayObject($varList);
					$cacheVar->importPropertie($this->varList);
					$this->varList = $cacheVar;
					uset($cacheVar, $varList);
					$useCache = true;
				}
			}
		}

		$transfromFile = self::$cachePath . '/' . $tplName . '.php';
		if (DEVELOPMENT || !file_exists($transfromFile) ||
				filemtime($transfromFile) < filemtime($tplFile)) {
			$this->transfromToPHP($tplFile, $transfromFile);
		}
        $TPL_VARS = $this->varList;
		include $transfromFile;
        
		//HTML Cache write
		if (self::$enableCache && self::$htmlCachePath != null && self::$cacheFlag == self::CACHE_FLAG_HTML) {
			$html = ob_get_contents();
			ob_flush();
			ob_end_clean();
			FileObject::saveContent($htmlCacheFile, $html);
		}
		if ($useCache) {
			return ViewCache::CACHE_USE_SUCC;
		}
	}

	private function transfromToPHP($tplFile, $transfromFile) {
		$content = file_get_contents($tplFile);

		//transfrom foreach statement
		$content = preg_replace_callback('/\{foreach\040+\$(\S+)\040+as\040+([\$a-zA-Z0-9_=>\040]+)}/is', function($matches) {
					$matches[1] = str_replace('.', '->', $matches[1]);
					if (preg_match('/\$([a-zA-Z0-9_]+)\040*=>\040*\$([a-zA-Z0-9_]+)/i', $matches[2], $setValue)) {
						$varName = "\$TPL_VARS->{$setValue[2]}";
						$keyName = "\$TPL_VARS->{$setValue[1]}=>";
					} elseif (preg_match('/\$([a-zA-Z0-9_]+)/i', $matches[2], $setValue)) {
						$varName = "\$TPL_VARS->{$setValue[1]}";
						$keyName = '';
					}
					$str = "<?php foreach(\$TPL_VARS->$matches[1] as $keyName $varName) { ?>";

					return $str;
				}, $content);
        $content = str_replace('{/foreach}', '<?php }?>', $content);
				

		//transfrom variable
		$content = preg_replace_callback('/\{\$([\.a-zA-Z0-9_\[\]]+)\}/i', function($matches) {
					$str = '<?php echo (string)$TPL_VARS->';
					$str .= str_replace('.', '->', $matches[1]);
					$str .= ';?>';
					return $str;
				}, $content);


		//transfrom define variable which is not controller set
		$content = preg_replace('/\{set\s+\$([\.a-zA-Z0-9_\x7f-\xff\[\]]+)\s*=\s*(.*)\}/i', "<?php \$TPL_VARS->$1=$2;?>", $content);

		//transfrom if statement
		$content = preg_replace_callback('/\{if\s+([^\{^\}]+)\}/i', function($matches) {
					$matches[1] = preg_replace('/\$([\[\]a-zA-Z0-9_\x7f-\xff]+)/i', '$TPL_VARS->$1', $matches[1]);
					$matches[1] = str_replace('.', '->', $matches[1]);
					return "<?php if($matches[1]) { ?>";
				}, $content);
		$content = preg_replace_callback('/\{elseif\s+([^\}\{]+)\}/i', function($matches) {
					$matches[1] = preg_replace('/\$([\[\]a-zA-Z0-9_\x7f-\xff]+)/i', '$TPL_VARS->$1', $matches[1]);
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
					$matches[2] = preg_replace('/\$([\[\]a-zA-Z0-9_\x7f-\xff]+)/i', '$TPL_VARS->$1', $matches[2]);
					return "<?php echo {$matches[1]}({$matches[2]});?>";
				}, $content);

		//clean the whitespace from beginning and end of line and html comment
        if(!DEVELOPMENT) {
            $content = preg_replace('/^\s*|\s*$|<!--.*-->|[\n\t\r]+/m', '', $content);
        }
        $content = preg_replace_callback('/\{table\s+\$([\.a-zA-Z0-9_\[\]]+)\s+\$([\.a-zA-Z0-9_\[\]]+)\}/i', function($matches) {
            $matches[1] = str_replace('.', '->', $matches[1]);
            $matches[2] = str_replace('.', '->', $matches[2]);
            if(empty($matches[3])) {
                return "<?php \$this->table(\$TPL_VARS->{$matches[1]},\$TPL_VARS->{$matches[2]});?>";
            } else {
                return "<?php \$this->table(\$TPL_VARS->{$matches[1]},\$TPL_VARS->{$matches[2]},{$matches[3]});?>";
            }
        }, $content);
        
		FileObject::saveContent($transfromFile, $content);
	}

	protected function importFile($file) {
		$this->display($file);
	}
    public function table($nav, $dataList, $defaultTpl = true) {
        $t = new Table($defaultTpl);
        $t->setNav($nav);
        $t->setListData($dataList);
        $t->renderer();
    }
}
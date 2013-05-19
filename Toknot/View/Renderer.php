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

class Renderer extends Object {

    private $varList = null;
    private $tplName = '';
    public $fileExtension = 'htm';
    public $scanPath = '';
    public $cachePath = '';
    private $cacheFile = '';

    public function __construct() {
        ;
    }

    public static function singleton() {
        parent::__singleton();
    }

    public function importVars($vars) {
        $this->varList = new ArrayObject($vars);
    }

    public function display($tplName) {
        $this->tplName = $this->scanPath . '/' . $tplName . '.' . $this->extension;
        if (!file_exists($this->tplName)) {
            throw new StandardException("{$this->tplName} not exists");
        }
        $this->cacheFile = $this->cachePath . '/' . $tplName . '.php';
        if (!file_exists($this->cacheFile) ||
                filemtime($this->cacheFile) < filemtime($this->tplName)) {
            $this->transfromPHP();
        }
        include_once $this->cacheFile;
    }

    private function transfromPHP() {
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

        ////transfrom invoke php function and echo return value
        $content = preg_replace_callback('/\{func\s+([a-zA-Z_\d]+)\((.*)\)\}/i', function ($matches) {
                    $matches[2] = str_replace('.', '->', $matches[2]);
                    $matches[2] = str_replace('/\$([\[\]a-zA-Z0-9_\x7f-\xff]+)/i', '$this->varList->$1', $matches[2]);
                    return "<?php if(function_exists({$matches[1]})){ echo {$matches[1]}({$matches[2]});} ?>";
                }, $content);

        file_put_contents($this->cacheFile, $content);
    }

}
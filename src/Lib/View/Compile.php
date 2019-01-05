<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Lib\View;

use Toknot\Boot\Kernel;
use Toknot\Lib\View\ViewData;

/**
 * 支持标签列表(不包括下面列表首个@符号)：
 * @:<TAG_NAME>                        <TAG_NAME>为占位符号名，可将块模板文件或模板代码插入此位置，通过view插入或在模板内使用 @insert 标签
 * @&<TAG_NAME>                        <TAG_NAME>为别名符号，续在view中注册关联的标签
 * @insert <TAG_NAME> <CODE_STRING>@    将<CODE_STRING>插入指定的<TAG_NAME>位置,其中@符号为结束符号
 * @load <TPL_NAME>                    将模板<TPL_NAME>导入当前位置
 * @widget <TPL_NAME> <params_list>    将模板<TPL_NAME>导入当前位置，但是导入的模板内部变量与外部隔离，通过<params_list>可向模板传递参数
 * @if <EXP>                           与 PHP if 表达式类似
 * @else if/elseif <EXP>               与 PHP elseif/else if 类似
 * @else                               等效 PHP else
 * @end                                等效 PHP 右花括号 }
 * @iter <EXP>                         等效 PHP foreach
 * @def <EXP>                          定义变量，<EXP>符合PHP规则
 * @route <ROUTE_NAME>                 输出路由表示的 URL 地址
 * @$<VAR>                             输出变量
 * @<function>(<PARAMS_EXP>)           输出函数返回值
 *
 */
class Compile {

    private $tplFile = '';
    private $content = '';
    private $cacheFile = '';
    private $varName = '';
    private $localName = '';
    public static $LT = '`';
    public static $RT = '`';
    protected $exclude = '';
    private $pageHoldTag = [];
    private $viewTag = [];
    private $hasLoadTag = false;
    private $insertTpl = [];
    private $widgetCount = 0;

    /**
     *
     * @var \Toknot\App\View\View
     */
    public $view = null;

    public function __construct($tplFile, $cacheFile) {
        $this->tplFile = $tplFile;
        $this->cacheFile = $cacheFile;
        $this->varName = '_tpl_' . substr($this->cacheFile, -10, 5);
        $this->exclude = '[^' . self::$RT . ']+';
    }

    /**
     * get current view complie content
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * convert controller action to url
     * 
     * @param string $route
     * @param array $params
     */
    public function route2Url($route, $params = []) {
        return $this->view->route2Url($route, $params);
    }

    /**
     * load view build cache file
     * 
     * @param ViewData $data   view data
     */
    public function load($data) {
        $name = $this->varName;
        $$name = $data;
        include $this->cacheFile;
    }

    /**
     * render view
     */
    public function render() {
        $this->hasLoadTag = false;
        $this->content = file_get_contents($this->tplFile);
        $this->viewTag = $this->view->getTags();
        $this->insertTpl = $this->view->getInsertTemplate();
        $this->loadTemplate();

        while ($this->linkTag()) {
            $this->loadTemplate();
        }

        $this->tplPageInsert();
        $this->complieControl();
        $this->clean();
        if ($this->cacheFile) {
            $this->save();
        }
    }

    protected function clean() {
        $pageHoldTag = array_keys($this->pageHoldTag);
        $this->content = str_replace($pageHoldTag, '', $this->content);
    }

    /**
     * view tag
     */
    protected function linkTag() {
        //`*TAG`
        $this->hasLoadTag = false;
        $this->replace('\&([a-z-0-9_]+)', [$this, 'replaceLinkTag']);
        return $this->hasLoadTag;
    }

    public function replaceLinkTag($m) {
        $tag = $m[1];
        if (!isset($this->viewTag[$tag])) {
            return Kernel::NOP;
        }
        $exp = $this->viewTag[$tag];
        if (strpos($exp, ':') === 0) {
            $this->hasLoadTag = true;
        } elseif (strpos($exp, 'load ') === 0) {
            $this->hasLoadTag = true;
        }
        return self::$LT . $exp . self::$RT;
    }

    protected function holdTag($tag) {
        $holdTag = "|~!--/*###$tag*/###--~|";
        $this->pageHoldTag[$holdTag] = 1;
        return $holdTag;
    }

    protected function tplPageInsert() {
        $pageInsert = [];
        $this->replace('insert\s+(' . $this->exclude . ')@', function($m) use(&$pageInsert) {
            list($tag, $code) = explode(Kernel::SP, trim($m[1]), 2);
            if (!isset($pageInsert[$tag])) {
                $pageInsert[$tag] = Kernel::NOP;
            }
            $pageInsert[$tag] .= $code;
            return Kernel::NOP;
        });
        foreach ($pageInsert as $tag => $code) {
            $this->content = str_replace($this->holdTag($tag), $code . $this->holdTag($tag), $this->content);
        }
    }

    public function tagBlock($m) {
        $tag = $m[1];
        if (!isset($this->insertTpl[$tag])) {
            return $this->holdTag($tag);
        }
        $tpl = '';
        foreach ($this->insertTpl[$tag] as $tplPath) {
            $tpl .= file_get_contents($tplPath);
        }
        return $tpl . $this->holdTag($tag);
    }

    public function tagLoad($m) {
        $path = $this->view->getTplRealpath($m[1]);
        return file_get_contents($path);
    }

    public function tagWidget($m) {
        $params = explode(Kernel::SP, $m[1]);
        $tpl = array_shift($params);
        if (empty($tpl)) {
            return Kernel::NOP;
        }
        $localVar = '';
        $paramsArr = $funcArr = [];
        foreach ($params as $arg) {
            $arg = trim($arg);
            list($vn, $v) = explode(Kernel::EQ, $arg);
            $v = trim($v);
            $vn = trim($vn);
            if (strpos($v, '$') === 0) {
                $paramsArr[] = $this->replaceVar($v);
                $localVar .= "\${$this->varName}->{$vn} = \${$vn};";
                $funcArr[] = "\${$vn}";
            } else {
                $localVar .= "\${$this->varName}->{$vn}='$vn';";
            }
        }

        $path = $this->view->getTplRealpath($tpl);
        $function = '_widget' . $this->widgetCount . '_' . md5($path . microtime())[1];
        $this->widgetCount++;
        $funcStr = implode(',', $funcArr);
        $callParam = implode(',', $paramsArr);
        $vcls = ViewData::class;
        return $this->t("if(!isset(\${$function})){\${$function} = function($funcStr){ \${$this->varName}= new $vcls;  $localVar")
                . file_get_contents($path) . $this->t("};} \${$function}($callParam);");
    }

    protected function loadTemplate() {
        //`:TAG` 将块文件插入此位置
        $this->replace(':([a-z-0-9_]+)', [$this, 'tagBlock']);

        //`load tpl/path` 在标签位置载入文件
        $this->replace('load\s+(' . $this->exclude . ')', [$this, 'tagLoad']);

        //`widget tpl arg='value' arg1=$var` 封闭区域
        $this->replace('widget\s+(' . $this->exclude . ')', [$this, 'tagWidget']);
    }

    private function t($str) {
        return "<?php $str ?>";
    }

    protected function complieControl() {

        //control structure expression must start space or line begin and until line end
        //`if $a>2`
        $this->replace('if\s+(' . $this->exclude . ')', function($m) {
            return $this->t('if(' . $this->replaceVar($m[1]) . ') {');
        });
        //`elseif $ab > 2`
        $this->replace('else\s*if\s+(' . $this->exclude . ')', function($m) {
            return $this->t('}elseif(' . $this->replaceVar($m[1]) . ') {');
        });
        //`else`
        $this->replace('else', '$1<?php } else {?>');
        //`end`
        $this->replace('end', $this->t('}'));
        //`iter $a as $b=>$c`
        $this->replace('iter\s+(' . $this->exclude . ')', function($m) {
            $arrValue = $this->replaceVar($m[1]);
            return $this->t("foreach($arrValue) {");
        });
        //`def $b='c'`
        $this->replace('def\s+(' . $this->exclude . ')', function($m) {
            $exp = $this->replaceVar($m[1]);
            return $this->t($exp);
        });
        //`route login@action params`
        $this->replace('route\s+([a-z0-9@\-\\\]+)(|\s+' . $this->exclude . ')', function($m) {
            $param = $m[2] ? ',' . $this->replaceVar($m[2]) : '';
            return '<?=$this->route2Url(' . Kernel::QUOTE . $m[1] . Kernel::QUOTE . $param . ')?>';
        });
        //`$aa`  echo $aa
        $this->replace('\$(' . $this->exclude . ')', "<?=\${$this->varName}->\$1?>");
        //`function()`
        $this->replace('([a-z_][a-z0-9_]*\()([^\)]*)\)', function($m) {
            return "<?={$m[1]}" . $this->replaceVar($m[2]) . ')?>';
        });
    }

    protected function replace($regContent, $replace) {
        $reg = '/' . self::$LT . $regContent . self::$RT . '/im';
        if (is_callable($replace)) {
            $this->content = preg_replace_callback($reg, $replace, $this->content);
        } else {
            $this->content = preg_replace($reg, $replace, $this->content);
        }
    }

    protected function replaceVar($m) {
        return preg_replace('/\$([_a-z][a-z0-9_]*)/i', "\${$this->varName}->\$1", $m);
    }

    protected function save() {
        file_put_contents($this->cacheFile, $this->content);
    }

}

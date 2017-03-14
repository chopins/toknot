<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Tool\Controller\Console;

use Toknot\Boot\Kernel;
use Toknot\Exception\BaseException;

/**
 *  ParseView
 */
class ParseView {

    private $dom;
    private $nodes;

    /**
     * @console parsehtml
     */
    public function createView() {
        $file = Kernel::single()->getOption('-h');
        $tagList = '';
        $this->init($file);
        $this->findBody($tagList);
        $this->generationView($tagList);
    }

    public function init($file) {
        if (!extension_loaded('dom')) {
            throw new BaseException('php dom extesion unload');
        }
        if (!file_exists($file)) {
            throw new BaseException("file $file not exists");
        }
        $this->dom = new \DOMDocument();
        $this->dom->loadHTMLFile($file, LIBXML_NONET);
        if (!$this->dom->hasChildNodes()) {
            throw new BaseException('the html not has child nodes');
        }
        $this->nodes = $this->dom->childNodes;
    }

    public function findBody(&$tagList) {
        foreach ($this->nodes as $node) {
            if ($this->getElementName($node) == 'html') {
                $this->nodes = $node->childNodes;
                return $this->findBody($tagList);
            } elseif ($this->isElement($node)) {
                $var = '$' . $this->getElementName($node);
            } elseif ($this->isText($node)) {
                continue;
            } elseif ($node->nodeType == XML_DOCUMENT_TYPE_NODE) {
                continue;
            }
            $this->generationTagBulid($node, $tagList, $var);
        }
    }

    public function isElement($node) {
        return $node->nodeType == XML_ELEMENT_NODE;
    }

    public function isText($node) {
        return $node->nodeType == XML_TEXT_NODE;
    }

    public function getElementName($node) {
        if ($node->nodeType == XML_ELEMENT_NODE) {
            return strtolower($node->tagName);
        }
        return '';
    }

    public function getTextTag($node) {
        if ($node->nodeType == XML_TEXT_NODE && !empty(trim($node->wholeText))) {
            return $node->wholeText;
        }
        return false;
    }

    public function getAttr($node) {
        $rest = '[';
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $rest .= "'{$attr->name}' => '{$attr->value}',";
            }
            $rest = substr($rest, 0, -1);
        }
        return $rest . ']';
    }

    public function generationTagBulid($node, &$code, $parentNode) {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $i => $ele) {
                if (($tag = $this->getElementName($ele))) {
                    $varName = $parentNode . ucfirst($tag) . $i;
                    $attr = $this->getAttr($ele);
                    $code .= "        {$varName} = \$this->{$tag}($parentNode, $attr);" . PHP_EOL;
                    $this->generationTagBulid($ele, $code, $varName);
                } elseif (($txt = $this->getTextTag($ele))) {
                    $code .= "        {$parentNode}->pushText('$txt');" . PHP_EOL;
                }
            }
        }
    }

    public function generationView($tagList) {
        $file = Kernel::single()->getOption('-o');
        $name = basename($file, '.php');
        $code = <<<EOF
<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Lib;

use Toknot\Share\View\View;
use Toknot\Share\View\Input;
use Toknot\Boot\Tookit;

class $name extends View {
    public function page() {
        \$layout = \$this->getLayoutInstance();
        \$this->setTitle('page title');
        \$head = \$this->head;
        \$body = \$this->body;
$tagList;
    }
}
EOF;
        echo $code;
        //file_put_contents($file, $code);
    }

    /**
     * 
     * @console layout
     */
    public function generationLayout() {
        $file = Kernel::single()->getOption(2);
        $name = basename($file, '.php');
        $code = <<<EOF
<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Layout;

use Toknot\Share\View\Layout;
use Toknot\Share\View\Tag;
use Toknot\Boot\Tookit;

class $name extends Layout {
    public \$headTag;
    public function html() {
        return [];
    }
    
    public function docType() {
        return ['version'=>'5'];
    }
   
    public function head(\$headTag) {
        \$this->headTag = \$headTag;
        Tag::meta(\$this->headTag, ['charset' => 'utf-8']);
        Tag::meta(\$this->headTag,
                ['name' => 'viewport', 'content' => 'width=device-width']);
    }

    public function body() {
        return [];
    }
    public function contanier() {
        \$body = \$this->getBody();
        //push other node
    }
}
EOF;
        file_put_contents($file, $code);
    }

}

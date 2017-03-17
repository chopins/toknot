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
use Toknot\Boot\Tookit;

/**
 *  ParseView
 */
class ParseView {

    private $dom;
    private $nodes;

    /**
     * create view from html file
     * 
     * -h parse html
     * -o output file
     * 
     * @console view.gen
     */
    public function createView() {
        $file = Kernel::single()->getOption('-h');
        $out = Kernel::single()->getOption('-o');
        $tagList = '';
        $this->init($file);
        $this->findBody($tagList);
        $this->generationView($tagList, $out);
    }

    /**
     * generation view class from dir of html
     * 
     * -d  save html dir for scan
     * -a  your app path
     * 
     * @console view.allgen
     */
    public function scan() {
        $dir = Kernel::single()->getOption('-d');
        $app = Kernel::single()->getOption('-a');
        $apppath = realpath($app);
        $appTopNs = ucwords(basename($apppath));
        $view = $apppath . DIRECTORY_SEPARATOR . $appTopNs . DIRECTORY_SEPARATOR . 'View';
        Tookit::dirWalk($dir, function($html) use($view) {
            $tagList = '';
            $this->init($html);
            $this->findBody($tagList);
            $name = basename($html, '.html');
            $this->generationView($tagList, "$view/$name");
        });
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
        $text = trim($node->wholeText);
        if ($node->nodeType == XML_TEXT_NODE && !$text) {
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

    public function generationView($tagList, $file) {
        $name = $file ? basename($file, '.php') : 'DefaultView';
        $code = <<<EOF
<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace View;

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
        if ($file) {
            file_put_contents($file, $code);
        } else {
            echo $code;
        }
    }

    /**
     * generation base layout class
     * 
     * [FILE] your_path/layout.php
     * 
     * @console layout
     */
    public function generationLayout() {
        $file = Kernel::single()->getOption(2);
        if (!$file) {
            throw new BaseException('must give a file path');
        }
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

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use SplObjectStorage;
use Toknot\Boot\Object;
use Toknot\Exception\BaseException;
use Toknot\Boot\GlobalFilter;
use Toknot\Exception\UndefinedPropertyException;

/**
 * View
 *
 * @author chopin
 */
abstract class TagBulid extends Object {

    /**
     *
     * @var string
     */
    protected $html = '';

    /**
     *
     * @var boolean
     */
    protected $singleTag = false;
    protected $innerHtml = '';

    /**
     *
     * @var string
     */
    protected $tagName = '';
    protected $tagStyle = [];
    protected $tagClass = [];
    protected $attr = [];
    private $srckey = null;
    private $resourceVer = null;
    private $host = null;

    /**
     *
     * @var Toknot\Share\View\Html;
     */
    protected static $page;

    /**
     *
     * @var SplObjectStorage
     */
    protected $iteratorArray;
    protected static $singleTagList = ['br', 'meta', 'link', 'input', 'img',
        'base', 'param', 'source', 'track'];
    public static $srcDefaultHost = '';

    protected function initTag($attr = []) {
        if (empty($this->tagName)) {
            $called = get_called_class();
            throw new BaseException("The $called::\$tagName of HTML tag name is empty");
        }
        $this->iteratorArray = new SplObjectStorage();
        $this->singleTag = in_array($this->tagName, self::$singleTagList);

        $this->begin($attr);
    }

    public static function addSingleTag($tagName) {
        array_push(self::$singleTagList, strtolower($tagName));
    }

    public function end() {
        $this->buildAttr();
        $this->buildClass();
        $this->buildStyle();
        if ($this->singleTag) {
            $this->html .= '/';
        }
        $this->html .= '>';
        if ($this->singleTag) {
            return $this->html .= '';
        }
        $this->html .= $this->innerHtml;
        $this->html .= $this->innerHTML();
        $this->html .= "</{$this->tagName}>";
        return $this;
    }

    protected function begin($attr = []) {
        $this->html .= "<{$this->tagName}";
        $this->html .= '';
        foreach ($attr as $k => $v) {
            switch ($k) {
                case 'class':
                    $this->addClass($v);
                    break;
                case 'style':
                    $this->cssStyle($v);
                    break;
                default:
                    $this->addAttr($k, $v);
                    break;
            }
        }
    }

    public function cssStyle($style) {
        if (is_array($style)) {
            foreach ($style as $sk => $sv) {
                $this->addStyle($sk, $sv);
            }
        } else {
            $item = explode(';', $style);
            foreach ($item as $vs) {
                if (empty($vs)) {
                    continue;
                }
                list($k, $v) = explode(':', $vs, 2);
                $this->addStyle($k, $v);
            }
        }
        return $this;
    }

    protected function buildClass() {
        if (empty($this->tagClass)) {
            return '';
        }
        $this->html .= ' class="' . implode($this->tagClass) . '"';
    }

    protected function buildStyle() {
        if (empty($this->tagStyle)) {
            return '';
        }
        $style = '';
        foreach ($this->tagStyle as $k => $v) {
            $style .= "$k:$v;";
        }
        $this->html .= " style=\"$style\"";
    }

    protected function buildAttr() {
        foreach ($this->attr as $attr => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (!is_scalar($value)) {
                continue;
            }

            if ($this->srckey == $attr && $this->resourceVer !== null) {
                $value = "{$value}?v={$this->resourceVer}";
            }

            if ($this->srckey == $attr) {
                $srcHost = $this->host ? $this->host : (self::$srcDefaultHost ? self::$srcDefaultHost : false);
                if (!$srcHost) {
                    list($pro) = explode('/', GlobalFilter::env('SERVER_PROTOCOL'));
                    $value = strtolower($pro) . "://" . GlobalFilter::env('HTTP_HOST') . $value;
                }
                $value = "$srcHost$value";
            }

            $v = addcslashes($value, '\'\\');
            $this->html .= " $attr=\"$v\"";
        }
    }

    public function addStyle($key, $v) {
        $this->tagStyle[$key] = $v;
        return $this;
    }

    public function addClass($class) {
        if (in_array($class, $this->tagClass)) {
            return;
        }
        array_push($this->tagClass, $class);
        return $this;
    }

    public function removeStyle($key) {
        unset($this->tagStyle[$key]);
        return $this;
    }

    public function removeClass($class) {
        $idx = array_search($class, $this->tagClass);
        if ($idx === false) {
            return;
        }
        unset($this->tagClass[$idx]);
        return $this;
    }

    /**
     * 
     * @param type $text
     * @return Toknot\Share\View\TagBulid
     */
    public function pushText($text) {
        $obj = new Text($text);
        $this->iteratorArray->attach($obj);
        return $this;
    }

    /**
     * 
     * @param type $text
     * @return Toknot\Share\View\TagBulid
     */
    public function setText($text) {
        foreach ($this->iteratorArray as $tag) {
            if ($tag instanceof Text) {
                $this->delTag($tag);
            }
        }
        return $this->pushText($text);
    }

    public function push(TagBulid $tag) {
        $this->iteratorArray->attach($tag);
        return $this;
    }

    public function unshift(TagBulid $tag) {
        $st = new \SplObjectStorage;
        $st->attach($tag);
        $st->addAll($this->iteratorArray);
        $this->iteratorArray = $st;
    }

    public function batchPush($nodes) {
        foreach ($nodes as $node) {
            $this->push($node);
        }
        return $this;
    }

    public function delTag($tag) {
        $this->iteratorArray->detach($tag);
        return $this;
    }

    public function innerHTML($html = '') {
        if ($html) {
            $this->iteratorArray->removeAll($this->iteratorArray);
            $this->innerHtml = $html;
            return;
        }
        $html = '';
        foreach ($this->iteratorArray as $tag) {
            $html .= $tag->getTags();
        }
        return $html;
    }

    public function getTags() {
        $this->end();
        return $this->html;
    }

    public function addAttr($attr, $value) {
        if ($attr == 'src') {
            $this->srckey = 'src';
        } elseif ($attr == 'href') {
            $this->srckey = 'href';
        }

        $this->attr[$attr] = $value;
        return $this;
    }

    public function attr($attr, $value = null) {
        if ($value === null) {
            return $this->getAttr($attr);
        }
        return $this->addAttr($attr, $value);
    }

    public function getAttr($attr) {
        if (isset($this->attr[$attr])) {
            return $this->attr[$attr];
        }
        return false;
    }

    public function addId($value) {
        $this->addAttr('id', $value);
        return $this;
    }

    public function addName($value) {
        $this->addAttr('name', $value);
        return $this;
    }

    public function setTitle($title) {
        $this->addAttr('title', $title);
        return $this;
    }

    final public function addHost($srcHost = false) {
        if ($srcHost !== false) {
            $this->host = $srcHost;
        }
        return $this;
    }

    final public function addVer($ver = false) {
        if ($ver !== false) {
            $this->resourceVer = $ver;
        }
        return $this;
    }

    final public function __get($name) {
        if (isset($this->attr[$name])) {
            return $this->attr[$name];
        }
        throw new UndefinedPropertyException($this, $name);
    }

    final public function __set($name, $value) {
        if (!is_scalar($value)) {
            throw new BaseException('tag of attributes must is scalar value');
        }
        $this->addAttr($name, $value);
    }

    public function copy($num = 1) {
        if ($num == 1) {
            return clone $this;
        }
        $res = [];
        for ($i = 0; $i < $num; $i++) {
            $res[] = clone $this;
        }
        return $res;
    }

    public function __clone() {
        $arr = new \SplObjectStorage();
        foreach ($this->iteratorArray as $obj) {
            $arr->attach(clone $obj);
        }
        $this->iteratorArray = $arr;
    }

    public function serialize() {
        return $this->getTags();
    }

    public function __toString() {
        return $this->getTags();
    }

    public function unserialize($html) {
        $this->innerHtml = $html;
    }

}

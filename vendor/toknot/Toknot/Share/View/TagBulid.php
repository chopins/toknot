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

    /**
     *
     * @var string
     */
    protected $tagName = '';
    protected $tagStyle = [];
    protected $tagClass = [];

    /**
     *
     * @var SplObjectStorage
     */
    protected $childStack;
    protected static $singleTagList = ['br', 'meta', 'link', 'input', 'img',
        'base', 'param', 'source', 'track'];

    protected function initTag($attr = []) {
        if (empty($this->tagName)) {
            $called = get_called_class();
            throw new BaseException("The $called::\$tagName of HTML tag name is empty");
        }
        $this->childStack = new SplObjectStorage();
        $this->singleTag = in_array($this->tagName, self::$singleTagList);

        $this->begin($attr);
    }

    public static function addSingleTag($tagName) {
        array_push(self::$singleTagList, strtolower($tagName));
    }

    public function end() {
        $this->buildClass();
        $this->buildStyle();
        if ($this->singleTag) {
            $this->html .= '/';
        }
        $this->html .= '>';
        if ($this->singleTag) {
            return $this->html .= '';
        }

        $this->ChildTag();
        $this->html .= "</{$this->tagName}>";
    }

    public function begin($attr = []) {
        $this->html .= "<{$this->tagName}";
        $this->html .= '';
        foreach ($attr as $k => $v) {
            switch ($k) {
                case 'class':
                    $this->addClass($v);
                    break;
                case 'style':
                    $this->parseStyle($v);
                    break;
                default:
                    $this->addAttr($k, $v);
                    break;
            }
        }
    }

    public function parseStyle($style) {
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
    }

    public function buildClass() {
        if (empty($this->tagClass)) {
            return '';
        }
        $this->html .= ' class="' . implode(' ', $this->tagClass) . '"';
    }

    public function buildStyle() {
        if (empty($this->tagStyle)) {
            return '';
        }
        $style = '';
        foreach ($this->tagStyle as $k => $v) {
            $style .= "$k:$v;";
        }
        $this->html .= " style=\"$style\"";
    }

    public function addStyle($key, $v) {
        $this->tagStyle[$key] = $v;
    }

    public function addClass($class) {
        if (in_array($class, $this->tagClass)) {
            return;
        }
        array_push($this->tagClass, $class);
    }

    public function removeStyle($key) {
        unset($this->tagStyle[$key]);
    }

    public function removeClass($class) {
        $idx = array_search($class, $this->tagClass);
        if ($idx === false) {
            return;
        }
        unset($this->tagClass[$idx]);
    }

    /**
     * 
     * @param type $text
     * @return \Toknot\Share\View\TagBulid
     */
    public function pushText($text) {
        $obj = new Text($text);
        $this->childStack->attach($obj);
        return $this;
    }

    public function push(TagBulid $tag) {
        $this->childStack->attach($tag);
    }

    public function delTag(TagBulid $tag) {
        $this->childStack->detach($tag);
    }

    public function ChildTag() {
        foreach ($this->childStack as $tag) {
            $this->html .= $tag->getTags();
        }
    }

    public function getTags() {
        $this->end();
        return $this->html;
    }

    public function addAttr($attr, $value) {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $v = addcslashes($value, '\'\\');
        $this->html .= " $attr=\"$v\"";
    }

}

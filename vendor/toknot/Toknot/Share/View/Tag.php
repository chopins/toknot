<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Boot\Tookit;
use Toknot\Exception\BaseException;

/**
 * html of Tag
 *
 * @author chopin
 */
class Tag extends TagBulid {

    /**
     *
     * @var \Toknot\Share\View\AnyTag;
     */
    protected static $body;


    /**
     *
     * @var \Toknot\Share\View\AnyTag;
     */
    protected static $title = null;

    /**
     *
     * @var \Toknot\Share\View\AnyTag;
     */
    protected static $head = null;

    public static function getHtml() {
        return self::$page->getTags();
    }

    /**
     * init html of page
     * 
     * @param array $attr   the html tag attributes
     * @param array $docType  The html document type
     */
    public static function html($attr = [], $docType = []) {
        self::$page = new Html($attr, $docType);
    }

    /**
     * 
     * @return Toknot\Share\View\AnyTag
     */
    public static function head() {
        self::$head = new AnyTag('head', []);
        self::$page->push(self::$head);
        return self::$head;
    }

    /**
     * 
     * @param array $attr
     * @return Toknot\Share\View\AnyTag
     */
    public static function body($attr = []) {
        self::$body = new AnyTag('body', $attr);
        self::$page->push(self::$body);
        return self::$body;
    }

    /**
     * the title tag will late insert head
     * 
     * @return Toknot\Share\View\AnyTag
     */
    public static function title($text) {
        self::$title = new AnyTag('title', []);
        self::$title->pushText($text);
        self::$head->push(self::$title);
        return self::$title;
    }

    /**
     * add text to Tag
     * 
     * @param \Toknot\Share\View\TagBulid $parentTag
     * @param string $text
     * @param Toknot\Share\View\TagBulid
     */
    public static function text(TagBulid $parentTag, $text) {
        return $parentTag->pushText($text);
    }

    /**
     * 
     * @param \Toknot\Share\View\TagBulid $parentTag
     * @param array $attr
     * @param string $text the textarea default contents string
     */
    public static function textarea(TagBulid $parentTag, $attr = [], $text = '') {
        $area = new AnyTag('textarea', $attr);
        $area->pushText($text);
        $parentTag->push($area);
    }

    /**
     * 
     * @param \Toknot\Share\View\TagBulid $parentTag
     * @param array $attr form tag attr or sub input tag
     * @return \Toknot\Share\View\AnyTag
     */
    public static function form(TagBulid $parentTag, $attr = []) {
        if (isset($attr['input']) && is_array($attr['input'])) {
            $inputs = $attr['input'];
            unset($attr['input']);
        }

        $form = new Form($attr);
        $form->inputs($inputs);
        $parentTag->push($form);
        return $form;
    }

    /**
     * 
     * @param \Toknot\Share\View\TagBulid $parentTag
     * @param string $src
     */
    public static function stylesheet(TagBulid $parentTag, $src) {
        $link = new AnyTag('link', ['href' => $src, 'rel' => 'stylesheet', 'type' => 'text/css']);
        $parentTag->push($link);
    }

    /**
     * 
     * @param \Toknot\Share\View\TagBulid $parentTag
     * @param array $attr
     */
    public static function script(TagBulid $parentTag, $attr = []) {
        $option = ['type' => 'text/javascript'];
        if (is_array($attr)) {
            $option = array_merge($option, $attr);
        }
        $script = new AnyTag('script', $option);
        if (is_string($attr)) {
            $script->pushText($attr);
        }
        $parentTag->push($script);
    }

    public static function style(TagBulid $parentTag, $code = '') {
        $option = ['type' => 'text/css'];
        $style = new AnyTag('style', $option);
        $style->pushText($code);
        $parentTag->push($style);
    }

    /**
     * 
     * @param \Toknot\Share\View\TagBulid $parentTag
     * @param array $attr   ['name'=>'XXX',
     *                       'option'=>['option1'=>['value'=>'1'],
     *                                  'option2'=>['value=>2,selected=>true],
     *                                  'option3'=>['value=>3]
     *                                 ]
     *                       ]
     * @return \Toknot\Share\View\Select
     */
    public static function select(TagBulid $parentTag, $attr) {
        $select = new Select($attr);
        $parentTag->push($select);
        return $select;
    }

    /**
     * 
     * @param string $name
     * @param TagBulid $tag The parent tag
     * @param array $attr
     * @return Toknot\Share\View\AnyTag
     * @throws BaseException
     */
    public static function __callStatic($name, $tag) {
        if (!isset($tag[0]) || !($tag[0] instanceof TagBulid)) {
            throw new BaseException(__CLASS__ . "::$name() expects parameter 1 to be " . __CLASS__ . " instance,null given");
        }
        Tookit::coalesce($tag, 1, []);

        $tagView = new AnyTag(strtolower($name), $tag[1]);

        $tag[0]->push($tagView);
        return $tagView;
    }

}

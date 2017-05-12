<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Exception\BaseException;
use Toknot\Boot\Tookit;

/**
 * html of Tag
 *
 * @author chopin
 */
class Tag extends TagBulid {

    /**
     *
     * @var Toknot\Share\View\AnyTag;
     */
    protected static $body;

    /**
     *
     * @var Toknot\Share\View\AnyTag;
     */
    protected static $title = null;

    /**
     *
     * @var Toknot\Share\View\AnyTag;
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
        self::$head = new Head();
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
    public static function title($text = '') {
        self::$title = new AnyTag('title', []);
        self::$title->pushText($text);
        self::$head->push(self::$title);
        return self::$title;
    }

    /**
     * add text to Tag
     * 
     * @param Toknot\Share\View\TagBulid $parentTag
     * @param string $text
     * @param Toknot\Share\View\TagBulid
     */
    public static function text(TagBulid $parentTag, $text) {
        return $parentTag->pushText($text);
    }

    /**
     * 
     * @param array $attr
     * @param string $text the textarea default contents string
     */
    public static function textarea($attr = [], $text = '') {
        $area = new AnyTag('textarea', $attr);
        $area->pushText($text);
        return $area;
    }

    /**
     * 
     * @param array $attr form tag attr or sub input tag
     * @return Toknot\Share\View\AnyTag
     */
    public static function form($attr = []) {
        if (isset($attr['inputs']) && is_array($attr['inputs'])) {
            $inputs = $attr['inputs'];
            unset($attr['inputs']);
        } else {
            $inputs = [];
        }

        $form = new Form($attr);
        $form->inputs($inputs);
        return $form;
    }

    public static function input($attr = []) {
        return new Input($attr);
    }

    public static function getForms() {
        return Form::getForms();
    }

    public static function getImages() {
        return AnyTag::getImages();
    }

    public static function getFrames() {
        return AnyTag::getFrames();
    }

    /**
     * 
     * @param string $src
     */
    public static function stylesheet($src) {
        $link = new AnyTag('link', ['href' => $src, 'rel' => 'stylesheet', 'type' => 'text/css']);
        return $link;
    }

    /**
     * 
     * @param array $attr
     */
    public static function script($attr = []) {
        $option = ['type' => 'text/javascript'];
        if (is_array($attr)) {
            $option = array_merge($option, $attr);
        }
        $script = new AnyTag('script', $option);
        if (is_string($attr)) {
            $script->pushText($attr);
        }
        return $script;
    }

    public static function style($code = '') {
        $option = ['type' => 'text/css'];
        $style = new AnyTag('style', $option);
        $style->pushText($code);
        return $style;
    }

    /**
     * 
     * @param array $attr   ['name'=>'XXX',
     *                       'option'=>['option1'=>['value'=>'1'],
     *                                  'option2'=>['value=>2,selected=>true],
     *                                  'option3'=>['value=>3]
     *                                 ]
     *                       ]
     * @return Toknot\Share\View\Select
     */
    public static function select($attr) {
        $select = new Select($attr);
        return $select;
    }

    /**
     * 
     * @param string $name
     * @param array $attr
     * @return Toknot\Share\View\AnyTag
     * @throws BaseException
     */
    public static function __callStatic($name, $attr) {
        Tookit::coalesce($attr, 0, []);
        $tagView = new AnyTag(strtolower($name), $attr[0]);
        return $tagView;
    }

}

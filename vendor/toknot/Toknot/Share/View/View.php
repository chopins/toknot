<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Boot\Object;
use Toknot\Share\View\Layout;
use Toknot\Share\View\Tag;
use Toknot\Boot\Kernel;
use Toknot\Exception\BaseException;

/**
 *  Layout
 *
 * @author chopin
 */
abstract class View extends Object {

    protected $param = [];

    /**
     *
     * @var \Toknot\Share\View\Layout
     */
    private $layoutIns = null;

    /**
     *
     * @var \Toknot\Share\View\AnyTag
     */
    protected $body;

    /**
     *
     * @var \Toknot\Share\View\AnyTag
     */
    protected $head;
    private static $layout = null;
    private static $title = '';

    final public function __construct($param = []) {
        $this->param = $param;

        $layoutClass = $this->layout();
        if (!is_subclass_of($layoutClass, 'Toknot\Share\View\Layout')) {
            throw new BaseException("$layoutClass must is sub of Toknot\Share\View\Layout");
        }
        $this->layoutIns = new $layoutClass($this, $param);
        $this->layoutIns->buildHtml();
    }

    final public function setHead(Layout $display) {
        $this->head = $display->getHead();
    }

    final public function setBody(Layout $display) {
        $this->body = $display->getBody();
    }

    /**
     * bulid main content of the html page
     */
    abstract public function page();

    final public static function setTitle($title) {
        self::$title = $title;
    }

    final public static function setLayout($layout) {
        self::$layout = $layout;
    }

    /**
     * 
     * @return string
     * @throws BaseException
     */
    final public function layout() {
        if (!empty(self::$layout)) {
            return self::$layout;
        }
        $class = get_called_class();
        throw new BaseException("View $class of layout not set");
    }

    /**
     * 
     * @return string
     */
    final public function title() {
        if (!empty(self::$title)) {
            return self::$title;
        }
        $class = get_called_class();
        return "View $class of title not set";
    }

    /**
     * 
     * @return Toknot\Share\View\Layout
     */
    final public function getLayoutInstance() {
        return $this->layoutIns;
    }

    /**
     * get html of page
     * 
     * @param array $param
     * @return string
     */
    final public static function html($param = []) {
        $ins = new static($param);
        return $ins->layoutIns->getHtmlDoc();
    }

    /**
     * create html tag
     * 
     * @param string $name  The tag name
     * @param array $argv   The tag attributes
     * @return Toknot\Share\View\Tag
     */
    final public function __call($name, $argv) {
        $argc = count($argv);
        if ($argc == 0) {
            return Tag::$name();
        }
        return self::invokeStatic($argc, $name, $argv, 'Toknot\Share\View\Tag');
    }

    final public function enableCsrf($form) {
        Tag::input($form,
                ['type' => 'hidden', 'name' => '_csrf_hash', 'id' => '_csrf_hash', 'value' => $this->param['_csrf_hash']]);
    }

    final public function route($route, $params = []) {
        Kernel::single()->routerIns()->url($route, $params);
    }

}

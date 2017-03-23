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
     * @var Toknot\Share\View\Layout
     */
    private $layoutIns = null;

    /**
     *
     * @var Toknot\Share\View\AnyTag
     */
    protected $body;

    /**
     *
     * @var Toknot\Share\View\AnyTag
     */
    protected $head;
    private static $title = '';

    final public function __construct(Layout $layout, $param = []) {
        $this->param = $param;

        if (!$layout instanceof Layout) {
            $layoutClass = get_class($layout);
            throw new BaseException("$layoutClass must is sub of Toknot\Share\View\Layout");
        }
        $this->layoutIns = $layout;
        $this->layoutIns->initPage();
        $this->head = $this->layoutIns->getHead();
        $this->body = $this->layoutIns->getBody();
        $this->page();
    }

    abstract public function page();

    final public static function setTitle($title) {
        self::$title = $title;
    }

    /**
     * set title
     */
    final public function title($title) {
        $this->layoutIns->title($title);
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
    final public static function html($layout, $param = []) {
        $ins = new static($layout, $param);
        return $ins->layoutIns->getHtmlDoc();
    }

    /**
     * create html tag
     * 
     * @param string $name  The tag name
     * @param array $argv   The tag attributes
     * @return Toknot\Share\View\TagBulid
     */
    final public function __call($name, $argv) {
        $argc = count($argv);
        if ($argc == 0) {
            return Tag::$name();
        }
        return self::invokeStatic($argc, $name, $argv, 'Toknot\Share\View\Tag');
    }

    final public function enableCsrf($form) {
        Tag::input($form, ['type' => 'hidden', 'name' => '_csrf_hash', 'id' => '_csrf_hash', 'value' => $this->param['_csrf_hash']]);
    }

    final public function route($route, $params = []) {
        Kernel::single()->routerIns()->url($route, $params);
    }

}

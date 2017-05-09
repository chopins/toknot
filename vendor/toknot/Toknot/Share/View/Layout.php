<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Share\View\Tag;
use Toknot\Boot\Object;

/**
 * Display
 *
 * invoke order:  
 *   $this->head()
 *   $this->title()
 * 
 */
abstract class Layout extends Object {

    /**
     * body
     *
     * @var Toknot\Share\View\AnyTag
     */
    private $body;

    /**
     *
     * @var array
     */
    private $param;

    /**
     * head
     *
     * @var \Toknot\Share\View\AnyTag
     */
    private $head;
    private $title;
    protected $htmlAttr = [];
    protected $bodyAttr = [];
    protected $htmlVer = 5;
    protected $htmlMode = 'strict';
    private $controller = null;
    private $route = null;
    protected $viewIns = null;

    /**
     * 
     * @param \Event\View\Layout $viewClass
     * @param array $param
     */
    public function __construct($viewClass, $contoller, $route) {
        $this->param = $contoller->v();
        $this->route = $route;
        $this->constructHtml();
        $this->view($viewClass);
        
    }

    final private function constructHtml() {
        Tag::html($this->htmlAttr, ['version' => $this->htmlVer, 'mode' => $this->htmlMode]);
        $this->head = Tag::head();
        $this->setPageTitle();
        $this->body = Tag::body($this->bodyAttr);
    }

    final public function setController($controller) {
        $this->controller = $controller;
    }

    final public function addHeadNode($tag, $attr = []) {
        $node = Tag::$tag($attr);
        $this->head->push($node);
        return $node;
    }

    final public function addBodyNode($tag, $attr = []) {
        if($tag instanceof TagBulid) {
            $this->body->push($tag);
            return $tag;
        }
        return Tag::$tag($this->body, $attr);
    }

    final public function getController() {
        return $this->controller;
    }

    final public function view($viewClass) {
        $this->viewIns = new $viewClass($this, $this->param);
        $this->viewIns->setControoler($this->controller);
        $this->viewIns->setRoute($this->route);
    }

    final public function setRoute($route) {
        $this->route = $route;
    }

    final public function getRoute() {
        return $this->route;
    }

    final public function setPageTitle() {
        $this->title = Tag::title('');
    }

    /**
     * return all html of page
     * 
     * @return string
     */
    final public function getHtmlDoc() {
        $this->head();
        $this->body();
        return Tag::getHtml();
    }

    /**
     * return body tag object
     * 
     * @return Toknot\Share\View\AnyTag
     */
    final public function getBody() {
        return $this->body;
    }

    /**
     * return the head tag object
     * 
     * @return Toknot\Share\View\AnyTag
     */
    final public function getHead() {
        return $this->head;
    }

    final public function title($text) {
        $this->title->setText($text);
    }

    /**
     * set body tag attr
     * 
     * @return array    The body tag attributes
     */
    final public function addBodyAttr($key, $value) {
        $this->bodyAttr[$key] = $value;
        return $this;
    }

    /**
     * set html tag attr
     * 
     * @return array   return html attributes
     */
    final public function addHtmlAttr($key, $value) {
        $this->htmlAttr[$key] = $value;
        return $this;
    }

    final public function setHtmlVer($ver) {
        $this->htmlVer = $ver;
        return $this;
    }

    final public function setHtmlMode($mode) {
        $this->htmlMode = $mode;
        return $this;
    }

    public function getHtmlAttr() {
        return $this->htmlAttr;
    }

    public function getBodyAttr() {
        return $this->bodyAttr;
    }

    public function getHtmlVer() {
        return $this->htmlVer;
    }

    public function getHtmlMode() {
        return $this->htmlMode;
    }

    abstract public function body();

    abstract public function head();
}

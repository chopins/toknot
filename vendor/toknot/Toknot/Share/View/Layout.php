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
use Toknot\Share\View\View;
use Toknot\Exception\BaseException;

/**
 * Display
 *
 * invoke order:  
 *   $this->head()
 *   $this->title()
 *   $this->body()
 * 
 */
abstract class Layout {

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

    /**
     *
     * @var \Event\View\Layout 
     */
    private $tpl;

    /**
     * 
     * @param \Event\View\Layout $tpl
     * @param array $param
     */
    final public function __construct(View $tpl, $param = []) {
        $this->tpl = $tpl;
        $this->param = $param;
    }

    final public function buildHtml() {
        $htmlOption = $this->html();
        $docType = $this->docType();

        Tag::html($htmlOption, $docType);
        $this->head = Tag::head();
        $this->tpl->setHead($this);
        $this->head();

        $this->setPageTitle();

        $this->setBodyAttributes();

        $this->tpl->setBody($this);
        $this->page();
    }

    final private function setBodyAttributes() {
        $body = $this->body();
        if (!is_array($body)) {
            $class = get_called_class($this);
            throw new BaseException("$class::body() must return array of body attributes");
        }
        $this->body = Tag::body($body);
    }

    final private function setPageTitle() {
        $text = $this->title();

        if (!is_scalar($text)) {
            $class = get_called_class($this->tpl);
            throw new BaseException("$class::title() must return title string");
        }
        Tag::title($text);
    }

    /**
     * return all html of page
     * 
     * @return string
     */
    final public function getHtmlDoc() {
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

    /**
     * set head tag
     */
    abstract public function head();

    /**
     * set body tag attr
     * 
     * @return array    The body tag attributes
     */
    public function body() {
        return [];
    }

    /**
     * set html tag attr
     * 
     * @return array   return html attributes
     */
    public function html() {
        return [];
    }

    public function docType() {
        return ['version' => 5];
    }

    final protected function page() {
        $this->tpl->page();
    }

    /**
     * set title string
     * 
     * @return string
     */
    final protected function title() {
        return $this->tpl->title();
    }

}

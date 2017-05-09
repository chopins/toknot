<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Layout;

use Toknot\Share\View\Layout;
use Toknot\Share\View\Tag;

/**
 * Default of layout
 *
 * @author chopin
 */
class DefaultLayout extends Layout {

    public $menuItemAttr = ['class' => 'pure-menu-item'];
    public $menuListAttr = ['class' => 'pure-menu-list'];
    public $bodyContanier;
    public $right;
    public $left;
    public $header;
    public $headerUl;
    public $headTag;
    public $leftMenu;
    public $section;

    public function __construct($viewClass, $contoller, $route) {
        $this->setHtmlMode('strict');
        $this->setHtmlVer(5);
        $this->addBodyAttr('onload', 'void(0);');
        $this->addHtmlAttr('lang', 'zh');
        parent::__construct($viewClass, $contoller, $route);
    }

    public function head() {
        $this->addHeadNode('meta', ['charset' => 'utf-8']);
        $this->addHeadNode('meta', ['http-equiv' => 'Content-Language', 'content' => 'zh']);
        $this->addHeadNode('meta', ['name' => 'viewport', 'content' => 'width=device-width']);
        $this->addHeadNode('stylesheet', '/purecss@0.6.2/build/pure-min.css')->addHost('https://unpkg.com');
        $this->addHeadNode('stylesheet', '/static/event.css')->addHost('')->addVer('0.1');
        $this->addHeadNode('script', ['src' => '/static/toknot.js/toknot.js'])->addHost('')->addVer('0.1');
    }

    public function body() {
        $this->section = Tag::div(['class' => 'section']);
        $this->addBodyNode($this->section);
        $this->header();
        $bodyContaier = Tag::div(['class' => 'contanier']);
        $this->section->push($bodyContaier);
        $this->bodyContanier = $this->grids($bodyContaier);

        $this->left();
        $this->right();

        $this->viewIns->page();
    }

    public function setCrumb($nav) {
        $tag = Tag::div(['class' => 'breadcrumb'])->pushText($nav);
        $this->right->push($tag);
    }

    public function rightBox() {
        $tag = Tag::div(['class' => 'right-box']);
        $this->right->push($tag);
        return $tag;
    }

    public function grids($parent) {
        $tag = Tag::div(['class' => 'pure-g']);
        $parent->push($tag);
        return $tag;
    }

    public function header() {
        $this->header = Tag::div(['class' => 'pure-u-1 pure-menu pure-menu-horizontal header']);
        $this->section->push($this->header);
        $g = $this->grids($this->header);
        $left = Tag::div(['class' => 'pure-u-1-12']);
        $g->push($left);
        $a = Tag::a(['class' => 'pure-menu-heading pure-menu-link'])
                ->pushText('ProcessHub');
        $left->push($a);
        $right = Tag::div(['class' => 'pure-u-11-12', 'style' => 'text-align:right;']);
        $g->push($right);
        $this->headerUl = Tag::ul($this->menuListAttr);
        $right->push($this->headerUl);
    }

    public function addHeadItem($item) {
        return $this->addMenuItem($this->headerUl, $item);
    }

    public function addMenuItem($parent, $item) {
        $text = self::coalesce($item, 0);
        $url = self::coalesce($item, 1, '#');
        $icon = self::coalesce($item, 2);

        $li = Tag::li($this->menuItemAttr);
        $parent->push($li);
        $a = Tag::a(['class' => 'pure-menu-link', 'href' => $url]);
        $li->push($a);
        $i = Tag::i(['class' => "icon fa $icon"]);
        $a->push($i);
        $a->pushText($text);
        return $li;
    }

    public function left() {
        $leftSider = Tag::div(['class' => 'pure-u-1-3 left']);
        $this->bodyContanier->push($leftSider);
        $this->leftMenu = Tag::ul($this->menuListAttr);
        $leftSider->push($this->leftMenu);
    }

    public function addLeftItem($item) {

        return $this->addMenuItem($this->leftMenu, $item);
    }

    public function right() {
        $this->right = Tag::div(['class' => 'right']);
        $this->bodyContanier->push($this->right);
    }

}

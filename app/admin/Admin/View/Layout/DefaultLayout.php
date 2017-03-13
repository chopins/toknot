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
use Toknot\Boot\Tookit;

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

    public function html() {
        return [];
    }
    
    public function docType() {
        return ['version'=>'5'];
    }
   
    public function head($headTag) {
        $this->headTag = $headTag;
        Tag::meta($this->headTag, ['charset' => 'utf-8']);
        Tag::meta($this->headTag,
                ['http-equiv' => 'Content-Language', 'content' => 'zh']);
        Tag::meta($this->headTag,
                ['name' => 'viewport', 'content' => 'width=device-width']);
        Tag::stylesheet($this->headTag, '/static/pure.css');
        Tag::stylesheet($this->headTag, '/static/event.css');

        Tag::script($this->headTag, ['src' => '/static/toknot.js']);
    }

    public function body() {
        return [];
    }

    public function contanier() {
        $this->section = Tag::div($this->getBody(), ['class' => 'section']);
        $this->header();
        $bodyContaier = Tag::div($this->section, ['class' => 'contanier']);

        $this->bodyContanier = $this->grids($bodyContaier);

        $this->left();
        $this->right();
    }

    public function setCrumb($nav) {
        Tag::div($this->right, ['class' => 'breadcrumb'])->pushText($nav);
    }
    public function rightBox() {
        return Tag::div($this->right, ['class' => 'right-box']);
    }
    public function grids($parent) {
        return Tag::div($parent, ['class' => 'pure-g']);
    }

    public function header() {
        $this->header = Tag::div($this->section,
                        ['class' => 'pure-u-1 pure-menu pure-menu-horizontal header']);
        $g = $this->grids($this->header);
        $left = Tag::div($g, ['class' => 'pure-u-1-12']);
        Tag::a($left, ['class' => 'pure-menu-heading pure-menu-link'])
                ->pushText('ProcessHub');
        $right = Tag::div($g,
                        ['class' => 'pure-u-11-12', 'style' => 'text-align:right;']);
        $this->headerUl = Tag::ul($right, $this->menuListAttr);
    }

    public function addHeadItem($item) {
        return $this->addMenuItem($this->headerUl, $item);
    }

    public function addMenuItem($parent, $item) {
        $text = Tookit::coalesce($item, 0);
        $url = Tookit::coalesce($item, 1, '#');
        $icon = Tookit::coalesce($item, 2);

        $li = Tag::li($parent, $this->menuItemAttr);
        $a = Tag::a($li, ['class' => 'pure-menu-link', 'href' => $url]);
        Tag::i($a, ['class' => "icon fa $icon"]);
        $a->pushText($text);
        return $li;
    }

    public function left() {
        $leftSider = Tag::div($this->bodyContanier,
                        ['class' => 'pure-u-1-3 left']);

        $this->leftMenu = Tag::ul($leftSider, $this->menuListAttr);
    }

    public function addLeftItem($item) {

        return $this->addMenuItem($this->leftMenu, $item);
    }

    public function right() {
        $this->right = Tag::div($this->bodyContanier,
                        ['class' => 'right']);
    }

}

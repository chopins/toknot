<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Admin\View\Lib;

use Toknot\Share\View\View;
use Toknot\Share\View\Input;
use Toknot\Boot\Tookit;
use Toknot\Boot\GlobalFilter;

abstract class BaseView extends View {

    /**
     *
     * @var Toknot\Share\View\Layout
     */
    public $layout = null;
    
    /**
     *
     * @var  Toknot\Share\View\AnyTag
     */
    public $body = null;
    public $rbox = null;

    abstract public function contanier();

    final public function init() {
        $this->layout = $this->getLayoutInstance();
    }
    

    final public function page() {
        $this->init();
        $this->layout->head();
        $this->layout->contanier();
        Input::addType('email');
        Tookit::coalesce($this->param, 'leftMenuSelected');
        Tookit::coalesce($this->param, 'headerMenuSelected');

        $this->buildFrame();
        $this->layout->setCrumb($this->param['pageNav']);
        $this->rbox = $this->layout->rightBox();
        $this->contanier();
        $this->showExecTime();
    }

    public function showExecTime() {
        $execTime = 'Exec Time:' . (microtime(true) - GlobalFilter::env('REQUEST_TIME_FLOAT'));
        $this->p($this->layout->right)->pushText($execTime);
    }

    public function addLeftMenu() {
        foreach ($this->param['leftMenu'] as $route => $item) {
            $li = $this->layout->addLeftItem($item);
            if ($this->param['leftMenuSelected'] == $route) {
                $li->addClass('pure-menu-selected');
            }
        }
    }

    public function addHeaderMenu() {
        foreach ($this->param['headerMenu'] as $route => $item) {
            $li = $this->layout->addHeadItem($item);
            if ($this->param['headerMenuSelected'] == $route) {
                $li->addClass('pure-menu-selected');
            }
        }
    }

    public function buildFrame() {
        $this->layout->contanier();
        $this->addHeaderMenu();
        $this->addLeftMenu();
    }

}

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
    protected $controler = null;
    protected $router = null;
    protected $routeStorage = [];
    protected $formStorage = [];

    final public function __construct(Layout $layout, $param = []) {
        $this->param = $param;

        if (!$layout instanceof Layout) {
            $layoutClass = get_class($layout);
            throw new BaseException("$layoutClass must is sub of Toknot\Share\View\Layout");
        }
        $this->layoutIns = $layout;
        $this->head = $this->layoutIns->getHead();
        $this->body = $this->layoutIns->getBody();
    }

    final public function setControoler($controller) {
        $this->controler = $controller;
    }

    final public function getController() {
        return $this->controler;
    }

    final public function setRoute($route) {
        $this->router = $route;
    }

    final public function getRoute() {
        return $this->router;
    }

    /**
     * 
     * @return Toknot\Share\View\Layout
     */
    final public function getLayoutInstance() {
        return $this->layoutIns;
    }

    /**
     * create html tag
     * 
     * @param string $name  The tag name
     * @param array $argv   The tag attributes
     * @return Toknot\Share\View\TagBulid
     */
    final public function __call($name, $argv = []) {
        $name = strtolower($name);
        $argc = count($argv);
        $node = $argc == 0 ? Tag::$name() : self::invokeStatic($argc, $name, $argv, 'Toknot\Share\View\Tag');
        if ($name == 'form') {
            $this->formStorage[] = $node;
        }
    
        return $node;
    }

    final public function route($route, $params = []) {
        $url = $this->router->url($route, $params);
        $method = $this->router->getMethods($route);

        $this->routeStorage[$route] = ['url' => $url, 'methods' => $method];
        return $url;
    }

    final public function getRouteStorage() {
        return $this->routeStorage;
    }

    final public function getFormStorage() {
        return $this->formStorage;
    }

}

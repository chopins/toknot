<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Control;
use Toknot\Di\Object;
use Toknot\Di\VisiterObject;
use Toknot\View\Renderer;


class AppContext extends Object{
    public $visiter = null;
    public $view = null;
    public function __construct() {
        $this->visiter = new VisiterObject();
        $this->view = Renderer::singleton();
    }
    public function __destruct() {
        ;
    }
    public function display() {
        
    }
}
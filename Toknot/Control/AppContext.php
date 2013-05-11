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
use Toknot\Di\DataObject;
use Toknot\Config\ConfigLoader;

class AppContext extends Object{
    public $visiter = null;
    public $view = null;
    public $D = null;
    public static function singleton() {
        return parent::__singleton();
    } 

    public function __construct() {
        $this->visiter = new VisiterObject();
        $this->D = new DataObject();
    }
    public function loadConfigure($ini) {
        return ConfigLoader::CFG($ini);
    }
    public function display($tplName) {
        $this->view = Renderer::singleton();
        $this->view->import($this->D);
        $this->view->display($tplName);
    }
}
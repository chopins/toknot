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
use Toknot\View\Renderer;
use Toknot\Config\ConfigLoader;
use Toknot\Db\ActiveRecord;

class AppContext extends Object{
    public $D = null;
    public static function singleton() {
        return parent::__singleton();
    } 

    public function __construct() {
        ConfigLoader::singleton();
    }
    public function loadConfigure($ini) {
        return ConfigLoader::loadCFG($ini);
    }
    public function getActiveRecord() {
        return ActiveRecord::singleton();
    }
    public function getView() {
        return null;
    }

    public function display($tplName) {
        $this->view = Renderer::singleton();
        $this->view->import($this->D);
        $this->view->display($tplName);
    }
}
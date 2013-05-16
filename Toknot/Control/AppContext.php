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
use Toknot\View\HTML;
use Toknot\View\XML;

final class AppContext extends Object{
    public $D = null;
    protected $uriOutRouterPath = null;
    public static function singleton() {
        return parent::__singleton();
    } 
    public function setURIOutRouterPath($part) {
        $this->uriOutRouterPath = $part;
    }
    public function getUriOutRouterPath() {
        return $this->uriOutRouterPath;
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
    public function newTemplateView() {
        return Renderer::singleton();
    }

    public function newHTMLView() {
        return HTML::singleton();
    }
    public function newXMLView() {
        return XML::singleton();
    }
    public function newJSONView() {
        
    }

    public function newPictureView() {
        
    }

    public function display($tplName) {
        $this->view = Renderer::singleton();
        $this->view->import($this->D);
        $this->view->display($tplName);
    }
    public function getParam() {
        
    }
}
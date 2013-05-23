<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Shop;

class ShopBase {

    protected $FMAI;
    protected $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $prems;
    protected $classGroup;
    public function __construct($FMAI) {
        $this->FMAI = $FMAI;
        $this->AR = $this->FMAI->getActiveRecord();
        $this->AppPath = __DIR__;
        $this->CFG = $this->FMAI->loadConfigure($this->AppPath . '/Config/config.ini');
        
        $this->FMAI->enableHTMLCache();
        
        $view = $this->FMAI->newTemplateView();
        $view->scanPath = __DIR__ . '/View';
        $view->cachePath = __DIR__ . '/Data/View';
        $view->fileExtension = 'html';
        $FMAI->checkAccess($this->perms,$this->classGroup);
    }

    public function CLI() {
        $this->GET();
    }

}
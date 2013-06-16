<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Shop;
use Toknot\User\ClassUserControl;

class ShopBase extends ClassUserControl {

    protected $FMAI;
    protected $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $prems;
    protected $classGroup;
    public function __construct($FMAI) {
        $this->FMAI = $FMAI;
        $this->CFG = $this->FMAI->loadConfigure($FMAI->appRoot . '/Config/config.ini');
        
        $this->AR = $this->FMAI->getActiveRecord();

        $this->AR->config($this->CFG->Database);
        
        $this->FMAI->enableHTMLCache($this->CFG->View);
        
        $this->view = $this->FMAI->newTemplateView($this->CFG->View);

        $FMAI->checkAccess($this , new \Toknot\User\Nobody);
    }

    public function CLI() {
        $this->GET();
    }

}
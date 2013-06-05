<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
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
        
        $this->FMAI->enableHTMLCache();
        
        $this->view = $this->FMAI->newTemplateView($this->CFG->View);

        $FMAI->checkAccess($this);
    }

    public function CLI() {
        $this->GET();
    }

}
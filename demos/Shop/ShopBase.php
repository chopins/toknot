<?php
namespace Shop;
use Toknot\User\ClassAccessControl;

class ShopBase extends ClassAccessControl {
    protected static $FMAI;
    protected static $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $permissions;
    protected $classGroup;
    public function __construct($FMAI) {
        $this->FMAI = $FMAI;
    //    $this->CFG = $this->FMAI->loadConfigure($FMAI->appRoot . '/Config/config.ini');

   //     $this->AR = $this->FMAI->getActiveRecord();

        //$this->AR->config($this->CFG->Database);

 //       $this->FMAI->enableHTMLCache();

  //      $this->view = $this->FMAI->newTemplateView($this->CFG->View);

 //       $FMAI->checkAccess($this);
    }

    public function CLI() {
        $this->GET();

    }
}

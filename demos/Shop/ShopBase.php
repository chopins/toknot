<?php
namespace Shop;
use Toknot\User\ClassAccessControl;
use Toknot\User\Nobody;
class ShopBase extends ClassAccessControl {
    protected static $FMAI;
    protected static $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $permissions;
    protected $classGroup;
    public function __construct($FMAI) {
        self::$FMAI = $FMAI;
        $this->CFG = self::$FMAI->loadConfigure(self::$FMAI->appRoot . '/Config/config.ini');
        
        $this->AR = self::$FMAI->getActiveRecord();

        //$this->AR->config($this->CFG->Database);
        
        //self::$FMAI->enableHTMLCache(self::$CFG->View);
        
        //$this->view = self::$FMAI->newTemplateView($this->CFG->View);

        $FMAI->checkAccess($this, new Nobody());
    }

    public function CLI() {
        $this->GET();
    }

}
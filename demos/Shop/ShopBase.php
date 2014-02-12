<?php
namespace Shop;
use Toknot\User\ClassAccessControl;
use Toknot\Control\ControllerInterface as CI;
use Toknot\User\Nobody;
use Toknot\Control\FMAI;

abstract class ShopBase extends ClassAccessControl implements CI\GET {
    protected static $FMAI;
    protected static $CFG;
    protected $AppPath;
    protected $AR;
    protected $view;
    protected $permissions;
    protected $classGroup;
    public function __construct(FMAI $FMAI) {
        self::$FMAI = $FMAI;
        //self::$CFG = self::$FMAI->loadConfigure(self::$FMAI->appRoot . '/Config/config.ini');
        
        //$this->AR = self::$FMAI->getActiveRecord();

        //$this->AR->config(self::$CFG->Database);
        
        //self::$FMAI->enableHTMLCache(self::$CFG->View);
        
        //$this->view = self::$FMAI->newTemplateView(self::$CFG->View);

        //$FMAI->checkAccess($this, new Nobody());
    }

    public function CLI() {
        $this->GET();
    }

}
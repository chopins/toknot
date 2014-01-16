<?php

namespace AppAdmin\Controller\User;

use Toknot\Admin\AdminBase;
use Toknot\Control\ControllerInterface as CI;

class Lists extends AdminBase implements CI\GET {

    protected $permissions = 0700;
    protected $gid = 0;
    protected $uid = 0;
  
    public function GET() {
        //self::$FMAI->setCurrentUser(new \Toknot\User\Nobody);
        self::$FMAI->invokeSubAction($this);
    }
    public $indexPerms = array('opType' => 'r','permissions' => 0400,'gid'=>0,'uid'=>0);
    public function index() {
        echo 'index';
    }

    public $actionPerms = array('opType' => 'r','permissions' => 0777,'gid'=>0,'uid'=>0);

    public function action() {
        echo 'action';
    }

}
<?php

namespace AppAdmin\Controller\User;

use Toknot\Admin\AdminBase;

class Lists extends AdminBase{

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
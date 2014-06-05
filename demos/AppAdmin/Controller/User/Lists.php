<?php

namespace AppAdmin\Controller\User;

use Toknot\Admin\AdminBase;

class Lists extends AdminBase{

    protected $permissions = 0700;
    protected $gid = 0;
    protected $uid = 0;
    const LISTS = 'M:0700,G:0,U:0';

    public function GET() {
        //self::$FMAI->setCurrentUser(new \Toknot\User\Nobody);
        self::$FMAI->invokeSubAction($this);
    }
    const INDEX = 'M:0400,P:r,G:0,U:0';
    public function index() {
        echo 'index';
    }

    const ACTION = 'M:0777,P:r,G:0,U:0';
    public function action() {
        echo 'action';
    }

}
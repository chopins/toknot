<?php
namespace AppAdmin\Controller;
            
use Toknot\Admin\AdminBase;

class Index extends AdminBase{
    public $perms = 0777;

    public function GET() {
        //$database = $this->AR->connect();
        print "hello world";

        //$this->display('index');
    }
 }
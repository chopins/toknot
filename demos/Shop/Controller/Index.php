<?php
namespace  Shop\Controller;
            
use Shop\ShopBase;
class Index extends ShopBase{     
    protected $permissions = 0770;

    public function GET() {
        $database = $this->AR->connect();
        print "hello world";        
        //self::$FMAI->display('index');
    }
 }
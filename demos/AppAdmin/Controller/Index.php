<?php
namespace  AppAdmin\Controller;
            
use Toknot\Admin\AdminBase;

class Index extends AdminBase{    
	public $perms = 0770;
    //public $operateType = self::CLASS_UPDATE;

    public function GET() {
        //$database = $this->AR->connect();
		$menu = new \Toknot\Admin\Menu(self::$FMAI);
		self::$FMAI->D->navList = $menu->getAllMenu();
		self::$FMAI->D->act = 'list';
        self::$FMAI->display('index');
    }
 }
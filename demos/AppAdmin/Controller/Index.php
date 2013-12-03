<?php
namespace  AppAdmin\Controller;
            
use Toknot\Admin\AdminBase;

class Index extends AdminBase{    
	public $perms = 0777;

    public function GET() {
        //$database = $this->AR->connect();
		$menu = new \Toknot\Admin\Menu();
		self::$FMAI->D->navList = $menu->getAllMenu();
		self::$FMAI->D->act = 'list';
        self::$FMAI->display('index');
    }
 }
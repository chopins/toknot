<?php
namespace  AppAdmin\Controller;
            
use Toknot\Admin\AdminBase;

class Index extends AdminBase{    
	public $perms = 0770;
    const INDEX = 'M:0770,P:r,G:0,U:0';

    //public $operateType = self::CLASS_UPDATE;

    public function GET() {
        //$database = $this->AR->connect();
		$menu = new \Toknot\Admin\Menu(self::$FMAI);
        self::$FMAI->D->tableNav = array(
            array('type'=>'checkbox','name'=>''),
            array('name'=>'User Name','type'=>'string')
        );
        self::$FMAI->D->tableData = array(
            array(
                array('type'=>'checkbox', 'selected'=>1),
                array('value'=>'test1')
            ),
            array(
                array('type'=>'checkbox'),
                array('value'=>'test2')
            )
            
        );
        if(self::$FMAI->getGET('is_ajax')) {
            self::$FMAI->D->isAjax=true;
        }
		self::$FMAI->D->navList = $menu->getAllMenu();
		self::$FMAI->D->act = 'list';
        self::$FMAI->display('index');
    }
 }
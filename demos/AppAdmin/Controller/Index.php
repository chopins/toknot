<?php
namespace  AppAdmin\Controller;
            
use Toknot\Lib\Admin\AdminBase;

class Index extends AdminBase{    
	public $perms = 0770;
    const INDEX = 'M:0770,P:r,G:0,U:0';

    //public $operateType = self::CLASS_UPDATE;

    public function GET() {
        //$database = $this->AR->connect();
		$menu = new \Toknot\Lib\Admin\Menu(\Toknot\Lib\FMAI::__singleton());
        $this->D->tableNav = array(
            array('type'=>'checkbox','name'=>''),
            array('name'=>'User Name','type'=>'string')
        );
        $this->D->tableData = array(
            array(
                array('type'=>'checkbox', 'selected'=>1),
                array('value'=>'test1')
            ),
            array(
                array('type'=>'checkbox'),
                array('value'=>'test2')
            )
            
        );
        if($this->getGET('is_ajax')) {
            $this->D->isAjax=true;
        }
		$this->D->navList = $menu->getAllMenu();
		$this->D->act = 'list';
        $this->display('index');
    }
 }
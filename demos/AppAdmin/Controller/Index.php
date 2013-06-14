<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace AppAdmin\Controller;

use Toknot\Admin\AdminBase;
use Toknot\Admin\MenuBox;

class Index extends AdminBase {
    
    public function GET() {
        print 'hello world';
        $catNav = new MenuBox();
        $catNav->setting = '网站设置';
        $catNav->setting->info = '摘要';
        $catNav->setting->info->default = true;
        $catNav->setting->info->control = 'Setting\Info';
        
        $catNav->setting->set = '设置';
        $catNav->setting->info->control = 'Setting\Set';
        
        $catNav->user = '用户管理';
        $catNav->user->add = '添加用户';
        $catNav->user->add->control = 'User\Add';
        $catNav->user->userList = '用户列表';
        $catNav->user->userList->control = 'User\UserList';
        
        $catNav->goods = '商品管理';
        $catNav->goods->add = '添加商品';
        $catNav->goods->add->control = 'Goods\Add';
        $catNav->goods->goodsList = '商品列表';
        $catNav->goods->goodsList->control = 'Goods\GoodsList';
        $catNav->goods->goodsCatAdd =  '添加分类';
        $catNav->goods->goodsCatAdd->control = 'Goods\CatAdd';
        $catNav->goods->goodsCatList = '分类列表';
        $catNav->goods->goodsCatList->control = 'Goods\CatList';
        
        $catNav->orders = '订单管理';

        $catNav->display();
        
    }
    public function POST() {
        
    }
}

?>

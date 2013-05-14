<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Shop\Controller;

use Shop\ShopContext;

class Index extends ShopContext {
    public function GET() {
        echo 'hello word';
        $config =\Toknot\Config\ConfigLoader::singleton('config.ini');
        
        //create ActiveRecord
        $active = new \Toknot\Db\ActiveRecord();
        $active->config($config->MySQL);
        //create database connect and return one DatabaseObject instance
        //one database is a instace
        $shopDatabase = $active->connect();
        
        //From product table get latest product with 50 number and is host and is not del
        $shopDatabase->product->hot = 5;
        $shopDatabase->product->isdel = 0;
        $hotProductList = $shopDatabase->product->readLatest(50);
        
        //From productCat table get product category list with 10 number and is not del
        $shopDatabase->productCat->isdel = 0;
        $shopDatabase->productCat->readLatest(10);
        
        //From product table and productCat get hot is 5 of latest product info list
        $joinTmpTable = $shopDatabase->tableJOIN($shopDatabase->product, $shopDatabase->productCat);
        $joinTmpTable->tableON($shopDatabase->product->catId, $shopDatabase->productCat->catid);
        $joinTmpTable->product->host = 5;
        $joinTmpTable->readLatest(10);

        $page = new \Toknot\View\Html();
        $pageHead = new \Toknot\View\Head();
        $page->append($pageHead);
        $pageBody = new \Toknot\View\Body();
        $page->append($pageBody);
        $table = new \Toknot\View\TableList($hotProductList);
        $pageBody->append($table);
        $this->toknot->display($page);
    }
}
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
        //create ActiveRecord
        $this->AR->config($this->CFG->Database);
        //create database connect and return one DatabaseObject instance
        //one database is a instace
        $shopDatabase = $this->AR->connect();
        $shopDatabase->product->hot->type = 'integer';
        $shopDatabase->product->isdel->type ='integer';
        $shopDatabase->product->id->type = 'integer';
        $shopDatabase->product->id->isPK = true;
        $shopDatabase->product->id->autoIncrement = true;
        
        $shopDatabase->productCat->isdel->type ='integer';
        
        $shopDatabase->productCat->id->type = 'integer';
        $shopDatabase->productCat->id->isPK = true;
        
        $shopDatabase->productCat->id->autoIncrement = true;
        $shopDatabase->createTable();
        //From product table get latest product with 50 number and is host and is not del
        $shopDatabase->product->hot = '5';
        $shopDatabase->product->isdel = 0;
        $hotProductList = $shopDatabase->product->findByAttr(50);

        //From productCat table get product category list with 10 number and is not del
        $shopDatabase->productCat->isdel = 0;
        $shopDatabase->productCat->findByAttr(10);

        //From product table and productCat get hot is 5 of latest product info list
        $joinTmpTable = $shopDatabase->tableJOIN($shopDatabase->product, $shopDatabase->productCat);
        $joinTmpTable->product->alias = 'p';
        $joinTmpTable->productCat->alias = 'c';
        $joinTmpTable->tableON($joinTmpTable->product->id, $joinTmpTable->productCat->id);
        $joinTmpTable->find(10);
        
        /*
         * <html>
         * <head>
         * </head>
         * <body>
         * </body>
         * </html>
         */
        $this->view->newPage('index');
        $meta = $this->view->newMeta('http-equiv="content-type" content="text/html; charset=UTF-8"');
        $title = $this->view->title('test');
        
        $this->view->display();
    }

}
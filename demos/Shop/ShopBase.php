<?php
namespace Shop;

use Toknot\Core\Object;
use Toknot\Db\ActiveRecord;
use Toknot\Config\ConfigLoader;

abstract class ShopBase extends Object  {
    
    protected $db;


    public function __init() {
        $ar = ActiveRecord::singleton();
        $cfg = ConfigLoader::CFG();
        $ar->config($cfg->Database);
        $this->db = $ar->connect();
    }

    public function CLI() {
        $this->GET();
    }

}
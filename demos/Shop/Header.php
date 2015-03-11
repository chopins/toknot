<?php
namespace Shop;

use Toknot\Boot\Object;

class Header extends Object {
    public function __init() {
       
    }

    public function CLI() {
        $this->GET();
    }

}
<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Shop;

class ShopContext {
    
    protected $toknot;

    public function __construct($toknot) {
        $this->toknot = $toknot;
    }
    public function CLI() {
        $this->GET();
    }
}
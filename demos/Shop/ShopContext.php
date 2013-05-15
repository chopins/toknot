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
    
    protected $AppContext;
    protected $CFG;
    protected $AppPath;
    protected $AR;
    public function __construct($AppContext) {
        $this->AppContext = $AppContext;
        $this->AR = $this->AppContext->getActiveRecord();
        $this->AppPath = __DIR__;
        $this->CFG = $this->AppContext->loadConfigure($this->AppPath.'/Config/config.ini');
    }
    public function CLI() {
        $this->GET();
    }
}
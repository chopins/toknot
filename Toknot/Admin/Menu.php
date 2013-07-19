<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Di\StringObject;
use Toknot\Config\ConfigLoader;
use Toknot\Di\FileObject;
use Toknot\Control\FMAI;

class Menu extends StringObject {

    public $control = null;
    public $subNav = array();

    public function setPropertie($propertie, $value) {
        $this->subNav[$propertie] = new MenuBox($value);
    }
	public function getAllMenu() {
		$FMAI = FMAI::getInstance();
		$file = FileObject::getRealPath($FMAI->appRoot, './Config/managelist.ini');
		$manageList = ConfigLoader::loadCfg($file);	
		foreach($manageList as &$manage) {
			if(isset($manage['sub'])) {
				foreach($manage['sub'] as $sub) {
					$manage['sub'] = explode('|', $sub);
				}
			}
		}
		var_dump($manageList);
		return $manageList;
	}
    public function addControllerForm() {
		
	}
	public function buildController() {

	}

	public function __get($name) {
        if (isset($this->subNav[$name])) {
            return $this->subNav[$name];
        }
    }
	public function createMenuTable($tableName) {
		$sql = "";
		
	}

	public function display() {
        
    }

}


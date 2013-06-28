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

class MenuBox extends StringObject {

    public $control = null;
    public $subNav = array();

    public function setPropertie($propertie, $value) {
        $this->subNav[$propertie] = new MenuBox($value);
    }
	public function getAllMenu() {
		return array();
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


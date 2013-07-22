<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Di\Object;
use Toknot\Config\ConfigLoader;
use Toknot\Di\FileObject;
use Toknot\Control\FMAI;

class Menu extends Object {

    public $control = null;
    public $subNav = array();

	public function getAllMenu() {
		$FMAI = FMAI::getInstance();
		$file = FileObject::getRealPath($FMAI->appRoot, './Config/managelist.ini');
		$manageList = ConfigLoader::loadCfg($file);	
		foreach($manageList as &$manage) {
			if(isset($manage['sub'])) {
				foreach($manage['sub'] as $key=> $sub) {
					$manage['sub'][$key] = explode('|', $sub);
				}
			}
		}
		return $manageList;
	}
  

}


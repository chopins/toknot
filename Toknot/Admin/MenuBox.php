<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Admin;

use Toknot\Di\StringObject;

class MenuBox extends StringObject {

    public $control = null;
    public $subNav = array();

    public function setPropertie($propertie, $value) {
        $this->subNav[$propertie] = new AdminNav($value);
    }

    public function __get($name) {
        if (isset($subNav[$name])) {
            return $subNav[$name];
        }
    }

    public function display() {
        
    }

}

?>

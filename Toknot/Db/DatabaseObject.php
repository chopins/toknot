<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */
namespace Toknot\Di;
use Toknot\Di\Object;

class DatabaseObject extends Object {
    public $dsn = null;
    public $user = null;
    public $password = null;
    public $connectInstance = null;
    public function setPropertie($propertie, $value) {
        $this->$propertie = new DbTableObject($propertie, $value);
    }
    
}